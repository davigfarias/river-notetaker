<?php

use App\Actions\GenerateNoteQuiz;
use App\Actions\GenerateNoteSummary;
use App\Actions\GenerateSummaryAudio;
use App\Actions\GetNote;
use App\Actions\GetReviewAgenda;
use App\Actions\Orchestrators\SubmitNoteQuizOrchestrator;
use App\Actions\RecordNoteReview;
use App\Actions\ResolveNoteQuizPool;
use App\Actions\SelectQuizQuestionsForSession;
use App\DTO\NotesDTO;
use App\DTO\ReviewAgendaDTO;
use App\Models\NoteAudio;
use App\Models\Notes;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use Livewire\Component;

new class extends Component
{
    public bool $showReviewModal = false;

    #[Locked]
    public ?int $noteIdUnderReview = null;

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
    public ?int $awaitingQuizNoteId = null;

    #[Session]
    public ?int $awaitingQuizSince = null;

    #[Session]
    public ?int $awaitingSummaryNoteId = null;

    #[Session]
    public ?int $awaitingSummarySince = null;

    #[Session]
    public ?string $awaitingSummaryBaseline = null;

    #[Session]
    public ?int $awaitingAudioNoteId = null;

    #[Session]
    public ?int $awaitingAudioSince = null;

    #[Computed]
    public function agenda(): ReviewAgendaDTO
    {
        return app(GetReviewAgenda::class)
            ->handle((int) session('access_token_id'))
            ->data;
    }

    #[Computed]
    public function noteUnderReview(): ?NotesDTO
    {
        if ($this->noteIdUnderReview === null) {
            return null;
        }

        return app(GetNote::class)
            ->handle($this->noteIdUnderReview, (int) session('access_token_id'))
            ->data;
    }

    public function openReview(int $noteId): void
    {
        if (! $this->isInQueue($noteId)) {
            Flux::toast(text: 'Esta nota não está na fila de revisão de hoje.', variant: 'warning');

            return;
        }

        $this->noteIdUnderReview = $noteId;
        $this->showReviewModal = true;

        unset($this->noteUnderReview);

        $this->startQuiz();
    }

    /**
     * Carrega o pool de perguntas cacheado para o resumo atual da nota e
     * sorteia as desta sessão. Se o pool ainda não existe para este resumo
     * (primeira revisão, ou resumo editado desde a última), dispara a
     * geração e a modal mostra o spinner até o polling encontrar o pool.
     */
    private function startQuiz(): void
    {
        $this->reset('quizQuestions', 'quizAnswers', 'quizResults', 'quizScore', 'lastRecalled');
        $this->stopAwaitingQuiz();

        $summary = (string) ($this->noteUnderReview?->summary ?? '');

        if ($summary === '') {
            return;
        }

        $pool = app(ResolveNoteQuizPool::class)
            ->handle($this->noteIdUnderReview, $this->contentHashFor($summary))
            ->data;

        if ($pool === null || $pool->isEmpty()) {
            $this->generateQuiz();

            return;
        }

        $this->quizQuestions = app(SelectQuizQuestionsForSession::class)->handle($pool)->data ?? [];
    }

    private function contentHashFor(string $summary): string
    {
        return hash('sha256', $summary);
    }

    private function generateQuiz(): void
    {
        if ($this->noteIdUnderReview === null) {
            return;
        }

        $outcome = app(GenerateNoteQuiz::class)->handle($this->noteIdUnderReview);

        if (! $outcome->success) {
            Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger');

            return;
        }

        $this->awaitingQuizNoteId = $this->noteIdUnderReview;
        $this->awaitingQuizSince = now()->timestamp;
    }

    #[Computed]
    public function awaitingQuiz(): bool
    {
        return $this->awaitingQuizNoteId !== null
            && $this->awaitingQuizNoteId === $this->noteIdUnderReview;
    }

    public function pollCheckQuiz(): void
    {
        $summary = (string) ($this->noteUnderReview?->summary ?? '');

        $pool = app(ResolveNoteQuizPool::class)
            ->handle($this->noteIdUnderReview, $this->contentHashFor($summary))
            ->data;

        if ($pool !== null && $pool->isNotEmpty()) {
            $this->quizQuestions = app(SelectQuizQuestionsForSession::class)->handle($pool)->data ?? [];
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
        $this->awaitingQuizNoteId = null;
        $this->awaitingQuizSince = null;
        unset($this->awaitingQuiz);
    }

    public function submitQuiz(SubmitNoteQuizOrchestrator $orchestrator): void
    {
        if ($this->noteIdUnderReview === null || $this->quizScore !== null) {
            return;
        }

        $outcome = $orchestrator->handle(
            $this->noteIdUnderReview,
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
                : 'Sem problema: ela volta na próxima aula.',
            variant: $this->lastRecalled ? 'success' : 'warning',
        );
    }

    /**
     * Saída de escape: entrega em branco, pontua zero e devolve a nota ao
     * primeiro degrau. Mais honesto do que chutar todas as perguntas.
     *
     * Se o pool ainda estiver sendo gerado (sem perguntas carregadas), não dá
     * para corrigir contra nada: registra a desistência direto, sem passar
     * pelo orquestrador de correção.
     */
    public function giveUp(SubmitNoteQuizOrchestrator $orchestrator): void
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
        if ($this->noteIdUnderReview === null || $this->quizScore !== null) {
            return;
        }

        $outcome = app(RecordNoteReview::class)->handle(
            $this->noteIdUnderReview,
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

        Flux::toast(text: 'Sem problema: ela volta na próxima aula.', variant: 'warning');
    }

    /**
     * Puxa a próxima nota devida sem fechar a modal.
     */
    public function nextNote(): void
    {
        unset($this->agenda, $this->noteUnderReview);

        $next = $this->agenda->due->first();

        if ($next === null) {
            $this->closeReview();
            Flux::toast(heading: 'Revisões de hoje concluídas', text: 'A fila da aula de hoje está vazia.', variant: 'success');

            return;
        }

        $this->noteIdUnderReview = $next->id;

        unset($this->noteUnderReview);

        $this->startQuiz();
    }

    public function closeReview(): void
    {
        $this->showReviewModal = false;
        $this->noteIdUnderReview = null;

        $this->reset('quizQuestions', 'quizAnswers', 'quizResults', 'quizScore', 'lastRecalled');
        $this->stopAwaitingQuiz();

        unset($this->noteUnderReview);
    }

    public function updatedShowReviewModal(): void
    {
        if (! $this->showReviewModal) {
            $this->closeReview();
        }
    }

    #[Computed]
    public function awaitingSummary(): bool
    {
        return $this->awaitingSummaryNoteId !== null
            && $this->awaitingSummaryNoteId === $this->noteIdUnderReview;
    }

    public function generateSummary(GenerateNoteSummary $action): void
    {
        if ($this->noteIdUnderReview === null) {
            return;
        }

        $noteId = $this->noteIdUnderReview;

        $outcome = $action->handle($noteId);

        match ($outcome->success) {
            true => Flux::toast(text: $outcome->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger'),
        };

        if ($outcome->success) {
            $this->awaitingSummaryNoteId = $noteId;
            $this->awaitingSummarySince = now()->timestamp;
            $this->awaitingSummaryBaseline = Notes::find($noteId)?->ai_summary;
            unset($this->awaitingSummary);
        }

        Flux::modal('confirm-regenerate-summary')->close();
    }

    public function pollCheckSummary(): void
    {
        $summary = Notes::find($this->noteIdUnderReview)?->ai_summary;

        if ($summary !== null && $summary !== $this->awaitingSummaryBaseline) {
            $this->stopAwaitingSummary();
            unset($this->noteUnderReview);

            return;
        }

        $deadline = (int) config('summarizer.job_timeout', 60) + 15;

        if ($this->awaitingSummarySince !== null
            && now()->timestamp - $this->awaitingSummarySince > $deadline) {
            $this->stopAwaitingSummary();

            Flux::toast(
                heading: 'Tempo esgotado',
                text: 'A geração do resumo demorou mais que o esperado. Tente novamente.',
                variant: 'danger',
            );
        }
    }

    protected function stopAwaitingSummary(): void
    {
        $this->awaitingSummaryNoteId = null;
        $this->awaitingSummarySince = null;
        $this->awaitingSummaryBaseline = null;
        unset($this->awaitingSummary);
    }

    /**
     * A locução da nota em revisão, quando já foi gerada para o resumo atual.
     */
    #[Computed]
    public function summaryAudio(): ?NoteAudio
    {
        if ($this->noteIdUnderReview === null) {
            return null;
        }

        $note = Notes::find($this->noteIdUnderReview);

        return $note === null ? null : app(GenerateSummaryAudio::class)->cachedAudioFor($note);
    }

    /**
     * URL da locução, assinada pelo conteúdo para o navegador poder cacheá-la
     * para sempre sem servir um áudio velho depois que o resumo muda.
     */
    #[Computed]
    public function summaryAudioUrl(): ?string
    {
        $audio = $this->summaryAudio;

        return $audio === null ? null : route('notas.resumo.audio', [
            'note' => $audio->note_id,
            'v' => substr($audio->signature, 0, 12),
        ]);
    }

    #[Computed]
    public function awaitingAudio(): bool
    {
        return $this->awaitingAudioNoteId !== null
            && $this->awaitingAudioNoteId === $this->noteIdUnderReview;
    }

    public function generateSummaryAudio(GenerateSummaryAudio $action): void
    {
        if ($this->noteIdUnderReview === null) {
            return;
        }

        $noteId = $this->noteIdUnderReview;

        $outcome = $action->handle($noteId);

        match ($outcome->success) {
            true => Flux::toast(text: $outcome->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger'),
        };

        if ($outcome->success) {
            $this->awaitingAudioNoteId = $noteId;
            $this->awaitingAudioSince = now()->timestamp;
            unset($this->awaitingAudio, $this->summaryAudio, $this->summaryAudioUrl);
        }
    }

    public function pollCheckAudio(): void
    {
        unset($this->summaryAudio, $this->summaryAudioUrl);

        if ($this->summaryAudio !== null) {
            $this->stopAwaitingAudio();

            return;
        }

        /*
         * O job marca o registro como falho assim que desiste, então a falha
         * aparece em segundos em vez de esperar o prazo inteiro — importante
         * porque um erro de cota volta em menos de um segundo.
         */
        $record = Notes::find($this->awaitingAudioNoteId)?->audio;

        if ($record?->status->isFailed()) {
            $this->stopAwaitingAudio();

            Flux::toast(
                heading: 'A locução não ficou pronta',
                text: $record->failure_reason ?? 'Não foi possível gerar a locução desta nota.',
                variant: 'danger',
            );

            return;
        }

        $deadline = (int) config('tts.job_timeout', 180) + 30;

        if ($this->awaitingAudioSince !== null
            && now()->timestamp - $this->awaitingAudioSince > $deadline) {
            $this->stopAwaitingAudio();

            Flux::toast(
                heading: 'A locução não ficou pronta',
                text: 'A geração demorou mais que o esperado. Tente de novo mais tarde.',
                variant: 'danger',
            );
        }
    }

    protected function stopAwaitingAudio(): void
    {
        $this->awaitingAudioNoteId = null;
        $this->awaitingAudioSince = null;
        unset($this->awaitingAudio);
    }

    private function isInQueue(int $noteId): bool
    {
        return $this->agenda->due->contains(fn ($review): bool => $review->id === $noteId);
    }
};
