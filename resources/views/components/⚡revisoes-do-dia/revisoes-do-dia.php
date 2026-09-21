<?php

use App\Actions\GenerateNoteSummary;
use App\Actions\GenerateSummaryAudio;
use App\Actions\GetNote;
use App\Actions\GetReviewAgenda;
use App\Actions\Orchestrators\SubmitNoteClozeOrchestrator;
use App\Actions\SelectClozeBlanks;
use App\Actions\TokenizeAnswerText;
use App\Actions\BuildClozeResultSegments;
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
     * Índices das palavras apagadas do resumo. São sorteados a cada abertura,
     * então a mesma nota nunca cobra exatamente as mesmas palavras duas vezes.
     *
     * @var array<int, int>
     */
    #[Locked]
    public array $clozeIndices = [];

    /**
     * O que o usuário digitou em cada lacuna, chaveado pelo índice da palavra.
     *
     * @var array<int|string, string>
     */
    public array $clozeInputs = [];

    /**
     * Correção lacuna a lacuna, preenchida depois de submeter.
     *
     * @var array<int, array{index: int, expected: string, given: string, correct: bool}>
     */
    #[Locked]
    public array $clozeBlanks = [];

    /**
     * Enquanto for null o usuário ainda está respondendo; preenchido, a modal
     * mostra o resultado.
     */
    #[Locked]
    public ?int $clozeScore = null;

    #[Locked]
    public ?bool $lastRecalled = null;

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

        $this->startCloze();
    }

    /**
     * Apaga uma parte das palavras do resumo escrito pelo aluno. É o mesmo
     * motor do modo estudo, só que sobre a nota em vez da pergunta do capítulo.
     */
    private function startCloze(): void
    {
        $this->reset('clozeIndices', 'clozeInputs', 'clozeBlanks', 'clozeScore', 'lastRecalled');

        unset($this->clozeSegments, $this->clozeResultSegments);

        $summary = (string) ($this->noteUnderReview?->summary ?? '');

        if ($summary === '') {
            return;
        }

        $tokens = app(TokenizeAnswerText::class)->handle($summary)->data ?? [];

        $this->clozeIndices = app(SelectClozeBlanks::class)->handle($tokens)->data ?? [];
    }

    /**
     * O resumo partido para a tela: segmentos de texto visível e as lacunas,
     * que carregam o índice a que cada input se liga.
     *
     * @return array<int, array{blank: bool, index?: int, text?: string}>
     */
    #[Computed]
    public function clozeSegments(): array
    {
        $summary = (string) ($this->noteUnderReview?->summary ?? '');

        if ($summary === '' || $this->clozeIndices === []) {
            return [];
        }

        $blankIndices = array_flip($this->clozeIndices);
        $tokens = app(TokenizeAnswerText::class)->handle($summary)->data ?? [];

        $segments = [];

        foreach ($tokens as $token) {
            if ($token['word'] && isset($blankIndices[$token['index']])) {
                $segments[] = ['blank' => true, 'index' => $token['index']];

                continue;
            }

            $segments[] = ['blank' => false, 'text' => $token['text']];
        }

        return $segments;
    }

    /**
     * O resumo corrigido: cada lacuna com o que foi digitado e o esperado.
     *
     * @return array<int, App\DTO\ClozeResultSegment>
     */
    #[Computed]
    public function clozeResultSegments(): array
    {
        if ($this->clozeBlanks === []) {
            return [];
        }

        return app(BuildClozeResultSegments::class)
            ->handle((string) ($this->noteUnderReview?->summary ?? ''), $this->clozeBlanks)
            ->data ?? [];
    }

    public function submitCloze(SubmitNoteClozeOrchestrator $orchestrator): void
    {
        if ($this->noteIdUnderReview === null || $this->clozeScore !== null) {
            return;
        }

        $outcome = $orchestrator->handle(
            $this->noteIdUnderReview,
            (int) session('access_token_id'),
            (string) ($this->noteUnderReview?->summary ?? ''),
            $this->clozeIndices,
            $this->clozeInputs,
        );

        if (! $outcome->success) {
            Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger');

            return;
        }

        $this->clozeScore = $outcome->data['score'];
        $this->lastRecalled = $outcome->data['recalled'];
        $this->clozeBlanks = $outcome->data['blanks'];

        unset($this->agenda, $this->clozeResultSegments);

        Flux::toast(
            text: $this->lastRecalled
                ? 'Revisão registrada. Próxima cobrança agendada.'
                : 'Sem problema: ela volta na próxima aula.',
            variant: $this->lastRecalled ? 'success' : 'warning',
        );
    }

    /**
     * Saída de escape: entrega em branco, pontua zero e devolve a nota ao
     * primeiro degrau. Mais honesto do que chutar todas as lacunas.
     */
    public function giveUp(SubmitNoteClozeOrchestrator $orchestrator): void
    {
        $this->clozeInputs = [];

        $this->submitCloze($orchestrator);
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

        $this->startCloze();
    }

    public function closeReview(): void
    {
        $this->showReviewModal = false;
        $this->noteIdUnderReview = null;

        $this->reset('clozeIndices', 'clozeInputs', 'clozeBlanks', 'clozeScore', 'lastRecalled');

        unset($this->noteUnderReview, $this->clozeSegments, $this->clozeResultSegments);
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
