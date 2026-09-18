<?php

use App\Actions\GetNote;
use App\Actions\GetReviewAgenda;
use App\Actions\RecordNoteReview;
use App\DTO\NotesDTO;
use App\DTO\ReviewAgendaDTO;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    public bool $showReviewModal = false;

    #[Locked]
    public ?int $noteIdUnderReview = null;

    /**
     * Os ganchos da nota ficam à vista; o corpo só desce depois do usuário
     * tentar lembrar. É isso que separa recall de reconhecimento.
     */
    public bool $revealed = false;

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
        $this->revealed = false;
        $this->showReviewModal = true;

        unset($this->noteUnderReview);
    }

    public function reveal(): void
    {
        $this->revealed = true;
    }

    /**
     * Registra o julgamento e puxa a próxima nota devida sem fechar a modal.
     */
    public function judge(bool $recalled, RecordNoteReview $recordNoteReview): void
    {
        if ($this->noteIdUnderReview === null) {
            return;
        }

        $check = $recordNoteReview->handle(
            $this->noteIdUnderReview,
            (int) session('access_token_id'),
            $recalled,
        );

        if (! $check->success) {
            Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');

            return;
        }

        unset($this->agenda, $this->noteUnderReview);

        $next = $this->agenda->due->first();

        if ($next === null) {
            $this->closeReview();
            Flux::toast(heading: 'Revisões de hoje concluídas', text: 'A fila da aula de hoje está vazia.', variant: 'success');

            return;
        }

        $this->noteIdUnderReview = $next->id;
        $this->revealed = false;

        unset($this->noteUnderReview);
    }

    public function closeReview(): void
    {
        $this->showReviewModal = false;
        $this->noteIdUnderReview = null;
        $this->revealed = false;

        unset($this->noteUnderReview);
    }

    public function updatedShowReviewModal(): void
    {
        if (! $this->showReviewModal) {
            $this->closeReview();
        }
    }

    private function isInQueue(int $noteId): bool
    {
        return $this->agenda->due->contains(fn ($review): bool => $review->id === $noteId);
    }
};
