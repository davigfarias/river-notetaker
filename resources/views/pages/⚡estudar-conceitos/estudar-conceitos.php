<?php

use App\Actions\Orchestrators\AnswerConceptCardOrchestrator;
use App\Actions\Orchestrators\StartConceptStudySessionOrchestrator;
use App\Actions\Orchestrators\UndoConceptAnswerOrchestrator;
use App\Actions\ToggleDeckCardStar;
use App\Enums\ConceptReviewResult;
use App\Models\ConceptDeck;
use App\Models\ConceptDeckCard;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Estudar conceitos')] class extends Component
{
    #[Locked]
    public int $deckId;

    #[Locked]
    public string $deckName = '';

    /**
     * Ids de ConceptDeckCard na ordem da fila; "não lembrei" põe o cartão de novo no fim.
     *
     * @var array<int, int>
     */
    #[Locked]
    public array $cardIds = [];

    /** Cartões distintos da sessão: cardIds cresce a cada "não lembrei". */
    #[Locked]
    public int $totalCards = 0;

    #[Locked]
    public int $completedCount = 0;

    #[Locked]
    public int $index = 0;

    #[Locked]
    public bool $revealed = false;

    /** Respondeu, mas o cartão só avança quando a animação de virar termina (advance()). */
    #[Locked]
    public bool $pendingAdvance = false;

    /**
     * Uma entrada por resposta, para o "Desfazer". A data anterior fica em texto ISO
     * porque o Livewire não reidrata objetos dentro de arrays.
     *
     * @var array<int, array{index: int, cardId: int, conceptId: int, reviewId: int, previousAcedCount: int, previousMissedCount: int, previousLastReviewedAt: string|null, requeued: bool}>
     */
    #[Locked]
    public array $history = [];

    /**
     * Deck de outro dono (ou apagado) cai em 404 aqui.
     */
    public function mount(int $deck, StartConceptStudySessionOrchestrator $orchestrator): void
    {
        $model = ConceptDeck::query()
            ->where('access_token_id', $this->tokenId())
            ->findOrFail($deck);

        $this->deckId = $model->id;
        $this->startSession($orchestrator, $model);
    }

    private function tokenId(): int
    {
        return (int) session('access_token_id');
    }

    #[Computed]
    public function card(): ?ConceptDeckCard
    {
        $id = $this->cardIds[$this->index] ?? null;

        if ($id === null) {
            return null;
        }

        return ConceptDeckCard::query()
            ->with('concept')
            ->where('concept_deck_id', $this->deckId)
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $this->tokenId()))
            ->find($id);
    }

    /** Há cartão na fila nesta posição, mas ele foi apagado ou saiu do deck. */
    #[Computed]
    public function isCardMissing(): bool
    {
        return $this->index < count($this->cardIds) && $this->card === null;
    }

    /**
     * Por que não há cartão para mostrar: 'empty' (deck sem conceitos), 'inactive'
     * (todos desativados) ou 'done' (sessão concluída).
     */
    #[Computed]
    public function endState(): string
    {
        if ($this->totalCards > 0) {
            return 'done';
        }

        return ConceptDeckCard::query()->where('concept_deck_id', $this->deckId)->exists() ? 'inactive' : 'empty';
    }

    #[Computed]
    public function progress(): int
    {
        return $this->totalCards === 0 ? 100 : min(100, (int) round(($this->completedCount / $this->totalCards) * 100));
    }

    #[Computed]
    public function canGoBack(): bool
    {
        return $this->history !== [];
    }

    public function reveal(): void
    {
        if ($this->card === null || $this->pendingAdvance) {
            return;
        }

        $this->revealed = true;
    }

    /**
     * Grava a resposta e desvira o cartão, mas só avança em advance()
     * (chamado pelo navegador quando a animação termina).
     */
    public function answer(string $result, AnswerConceptCardOrchestrator $orchestrator): void
    {
        if (! $this->revealed || $this->pendingAdvance) {
            return;
        }

        $reviewResult = ConceptReviewResult::tryFrom($result);
        $card = $this->card;

        if ($reviewResult === null || $card === null) {
            return;
        }

        $snapshotCheck = $orchestrator->handle($this->tokenId(), $card->concept_id, $reviewResult);

        if (! $snapshotCheck->success) {
            Flux::toast(duration: 3000, heading: 'Ocorreu um erro', text: $snapshotCheck->message, variant: 'danger');

            return;
        }

        $this->history[] = [
            'index' => $this->index,
            'cardId' => $card->id,
            'conceptId' => $card->concept_id,
            ...$snapshotCheck->data->toArray(),
            'requeued' => $reviewResult === ConceptReviewResult::Forgot,
        ];

        if ($reviewResult === ConceptReviewResult::Forgot) {
            $this->cardIds[] = $card->id;
        } else {
            $this->completedCount++;
        }

        $this->revealed = false;
        $this->pendingAdvance = true;
    }

    public function advance(): void
    {
        if (! $this->pendingAdvance) {
            return;
        }

        $this->index++;
        $this->pendingAdvance = false;
        $this->revealed = false;
        unset($this->card, $this->isCardMissing);
    }

    /**
     * Cartão que sumiu do deck no meio da sessão: pula sem gravar resposta.
     */
    public function skipMissing(): void
    {
        if (! $this->isCardMissing) {
            return;
        }

        $this->index++;
        $this->completedCount++;
        $this->pendingAdvance = false;
        $this->revealed = false;
        unset($this->card, $this->isCardMissing);
    }

    public function goBack(UndoConceptAnswerOrchestrator $orchestrator): void
    {
        if ($this->history === []) {
            return;
        }

        $entry = array_pop($this->history);

        $check = $orchestrator->handle(
            $this->tokenId(),
            $entry['conceptId'],
            $entry['reviewId'],
            $entry['previousAcedCount'],
            $entry['previousMissedCount'],
            $entry['previousLastReviewedAt'],
        );

        if (! $check->success) {
            $this->history[] = $entry;
            Flux::toast(duration: 3000, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');

            return;
        }

        if ($entry['requeued']) {
            array_pop($this->cardIds);
        } else {
            $this->completedCount--;
        }

        $this->index = $entry['index'];
        $this->pendingAdvance = false;
        $this->revealed = true;
        unset($this->card, $this->isCardMissing);
    }

    public function toggleStar(ToggleDeckCardStar $action): void
    {
        $card = $this->card;

        if ($card === null) {
            return;
        }

        $check = $action->handle($card->id, $this->tokenId());

        if (! $check->success) {
            Flux::toast(duration: 3000, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');

            return;
        }

        unset($this->card);
    }

    public function restart(StartConceptStudySessionOrchestrator $orchestrator): void
    {
        $deck = ConceptDeck::query()
            ->where('access_token_id', $this->tokenId())
            ->findOrFail($this->deckId);

        $this->startSession($orchestrator, $deck);
    }

    private function startSession(StartConceptStudySessionOrchestrator $orchestrator, ConceptDeck $deck): void
    {
        $check = $orchestrator->handle($this->tokenId(), $deck);

        if (! $check->success) {
            Flux::toast(duration: 3000, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');
        }

        $this->deckName = $deck->name;
        $this->cardIds = $check->success ? $check->data->cardIds : [];
        $this->totalCards = count($this->cardIds);
        $this->completedCount = 0;
        $this->index = 0;
        $this->revealed = false;
        $this->pendingAdvance = false;
        $this->history = [];
        unset($this->card, $this->isCardMissing, $this->endState);
    }
};
