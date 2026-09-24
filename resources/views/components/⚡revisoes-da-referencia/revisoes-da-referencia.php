<?php

use App\Actions\GenerateReadingNoteQuiz;
use App\Actions\GetReadingNote;
use App\Actions\GetReferenceReviewAgenda;
use App\Actions\Orchestrators\SubmitReadingNoteQuizOrchestrator;
use App\Actions\RecordReadingNoteReview;
use App\Actions\ResolveReadingNoteQuizPool;
use App\Actions\SelectReadingNoteQuizQuestionsForSession;
use App\DTO\ReferenceReviewAgendaDTO;
use App\Models\ReadingNote;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use Livewire\Component;

new class extends Component
{
    public int $referenceMaterialId;

    public bool $showReviewModal = false;

    #[Locked]
    public ?int $readingNoteIdUnderReview = null;

    /**
     * As perguntas sorteadas para esta sessão: id, texto e alternativas já
     * embaralhadas, sem indicar qual é a correta — isso fica só no banco,
     * comparado no servidor em submitQuiz().
     *
     * @var array<int, array{id: int, question: string, options: array<int, string>}>
     */
    #[Locked]
    public array $quizQuestions = [];

    /**
     * A alternativa escolhida em cada pergunta, chaveada pelo id da pergunta.
     *
     * @var array<int, string>
     */
    public array $quizAnswers = [];

    /**
     * Correção pergunta a pergunta, preenchida depois de submeter.
     *
     * @var array<int, array{question: string, chosen: string, correct_answer: string, correct: bool}>
     */
    #[Locked]
    public array $quizResults = [];

    /**
     * Enquanto for null o usuário ainda está respondendo; preenchido, a modal
     * mostra o resultado.
     */
    #[Locked]
    public ?int $quizScore = null;

    #[Locked]
    public ?bool $lastRecalled = null;

    #[Session]
    public ?int $awaitingQuizReadingNoteId = null;

    #[Session]
    public ?int $awaitingQuizSince = null;

    public function mount(int $referenceMaterialId): void
    {
        $this->referenceMaterialId = $referenceMaterialId;
    }

    #[Computed]
    public function agenda(): ReferenceReviewAgendaDTO
    {
        return app(GetReferenceReviewAgenda::class)
            ->handle($this->referenceMaterialId, (int) session('access_token_id'))
            ->data;
    }

    #[Computed]
    public function readingNoteUnderReview(): ?ReadingNote
    {
        if ($this->readingNoteIdUnderReview === null) {
            return null;
        }

        return app(GetReadingNote::class)
            ->handle($this->readingNoteIdUnderReview, (int) session('access_token_id'))
            ->data;
    }

    public function openReview(int $readingNoteId): void
    {
        if (! $this->isInQueue($readingNoteId)) {
            Flux::toast(text: 'Esta anotação não está na fila de revisão de hoje.', variant: 'warning');

            return;
        }

        $this->readingNoteIdUnderReview = $readingNoteId;
        $this->showReviewModal = true;

        unset($this->readingNoteUnderReview);

        $this->startQuiz();
    }

    /**
     * Carrega o pool de perguntas cacheado para o conteúdo atual da anotação
     * e sorteia as desta sessão. Se o pool ainda não existe para esse
     * conteúdo (primeira revisão, ou anotação editada desde a última),
     * dispara a geração e a modal mostra o spinner até o polling encontrar
     * o pool.
     */
    private function startQuiz(): void
    {
        $this->reset('quizQuestions', 'quizAnswers', 'quizResults', 'quizScore', 'lastRecalled');
        $this->stopAwaitingQuiz();

        $content = $this->contentFor($this->readingNoteUnderReview);

        if ($content === '') {
            return;
        }

        $pool = app(ResolveReadingNoteQuizPool::class)
            ->handle($this->readingNoteIdUnderReview, $this->contentHashFor($content))
            ->data;

        if ($pool === null || $pool->isEmpty()) {
            $this->generateQuiz();

            return;
        }

        $this->quizQuestions = app(SelectReadingNoteQuizQuestionsForSession::class)->handle($pool)->data ?? [];
    }

    private function contentFor(?ReadingNote $note): string
    {
        if ($note === null) {
            return '';
        }

        return trim(($note->title !== null ? "{$note->title}\n\n" : '').$note->body);
    }

    private function contentHashFor(string $content): string
    {
        return hash('sha256', $content);
    }

    private function generateQuiz(): void
    {
        if ($this->readingNoteIdUnderReview === null) {
            return;
        }

        $outcome = app(GenerateReadingNoteQuiz::class)->handle($this->readingNoteIdUnderReview);

        if (! $outcome->success) {
            Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger');

            return;
        }

        $this->awaitingQuizReadingNoteId = $this->readingNoteIdUnderReview;
        $this->awaitingQuizSince = now()->timestamp;
    }

    #[Computed]
    public function awaitingQuiz(): bool
    {
        return $this->awaitingQuizReadingNoteId !== null
            && $this->awaitingQuizReadingNoteId === $this->readingNoteIdUnderReview;
    }

    public function pollCheckQuiz(): void
    {
        $content = $this->contentFor($this->readingNoteUnderReview);

        $pool = app(ResolveReadingNoteQuizPool::class)
            ->handle($this->readingNoteIdUnderReview, $this->contentHashFor($content))
            ->data;

        if ($pool !== null && $pool->isNotEmpty()) {
            $this->quizQuestions = app(SelectReadingNoteQuizQuestionsForSession::class)->handle($pool)->data ?? [];
            $this->stopAwaitingQuiz();

            return;
        }

        $deadline = (int) config('quiz.job_timeout', 60) + 15;

        if ($this->awaitingQuizSince !== null
            && now()->timestamp - $this->awaitingQuizSince > $deadline) {
            $this->stopAwaitingQuiz();

            Flux::toast(
                heading: 'Tempo esgotado',
                text: 'A geração das perguntas demorou mais que o esperado. Tente novamente.',
                variant: 'danger',
            );
        }
    }

    protected function stopAwaitingQuiz(): void
    {
        $this->awaitingQuizReadingNoteId = null;
        $this->awaitingQuizSince = null;
        unset($this->awaitingQuiz);
    }

    public function submitQuiz(SubmitReadingNoteQuizOrchestrator $orchestrator): void
    {
        if ($this->readingNoteIdUnderReview === null || $this->quizScore !== null) {
            return;
        }

        $outcome = $orchestrator->handle(
            $this->readingNoteIdUnderReview,
            (int) session('access_token_id'),
            array_column($this->quizQuestions, 'id'),
            $this->quizAnswers,
        );

        if (! $outcome->success) {
            Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger');

            return;
        }

        $this->quizScore = $outcome->data['score'];
        $this->lastRecalled = $outcome->data['recalled'];
        $this->quizResults = $outcome->data['results'];

        unset($this->agenda);

        Flux::toast(
            text: $this->lastRecalled
                ? 'Revisão registrada. Próxima cobrança agendada.'
                : 'Sem problema: ela volta amanhã.',
            variant: $this->lastRecalled ? 'success' : 'warning',
        );
    }

    /**
     * Saída de escape: entrega em branco, pontua zero e devolve a anotação
     * ao primeiro degrau. Mais honesto do que chutar todas as perguntas.
     *
     * Se o pool ainda estiver sendo gerado (sem perguntas carregadas), não
     * dá para corrigir contra nada: registra a desistência direto, sem
     * passar pelo orquestrador de correção.
     */
    public function giveUp(SubmitReadingNoteQuizOrchestrator $orchestrator): void
    {
        if ($this->quizQuestions === []) {
            $this->recordGiveUpWithoutQuestions();

            return;
        }

        $this->quizAnswers = [];

        $this->submitQuiz($orchestrator);
    }

    private function recordGiveUpWithoutQuestions(): void
    {
        if ($this->readingNoteIdUnderReview === null || $this->quizScore !== null) {
            return;
        }

        $outcome = app(RecordReadingNoteReview::class)->handle(
            $this->readingNoteIdUnderReview,
            (int) session('access_token_id'),
            false,
        );

        if (! $outcome->success) {
            Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger');

            return;
        }

        $this->quizScore = 0;
        $this->lastRecalled = false;
        $this->quizResults = [];

        unset($this->agenda);

        Flux::toast(text: 'Sem problema: ela volta amanhã.', variant: 'warning');
    }

    /**
     * Puxa a próxima anotação devida sem fechar a modal.
     */
    public function nextNote(): void
    {
        unset($this->agenda, $this->readingNoteUnderReview);

        $next = $this->agenda->due->first();

        if ($next === null) {
            $this->closeReview();
            Flux::toast(heading: 'Revisões concluídas', text: 'A fila de revisão desta referência está vazia.', variant: 'success');

            return;
        }

        $this->readingNoteIdUnderReview = $next->id;

        unset($this->readingNoteUnderReview);

        $this->startQuiz();
    }

    public function closeReview(): void
    {
        $this->showReviewModal = false;
        $this->readingNoteIdUnderReview = null;

        $this->reset('quizQuestions', 'quizAnswers', 'quizResults', 'quizScore', 'lastRecalled');
        $this->stopAwaitingQuiz();

        unset($this->readingNoteUnderReview);
    }

    public function updatedShowReviewModal(): void
    {
        if (! $this->showReviewModal) {
            $this->closeReview();
        }
    }

    private function isInQueue(int $readingNoteId): bool
    {
        return $this->agenda->due->contains(fn ($review): bool => $review->id === $readingNoteId);
    }
};
