<?php

use App\Actions\AddConceptsToDeck;
use App\Actions\DeleteConceptDeck;
use App\Actions\GetConceptsByLetter;
use App\Actions\RemoveCardsFromDeck;
use App\Actions\RenameConceptDeck;
use App\Actions\SearchConcept;
use App\Actions\SetDeckCardsActiveState;
use App\Actions\ToggleDeckCardStar;
use App\DTO\ConceptDeckForm;
use App\Models\ConceptDeck;
use App\Models\ConceptDeckCard;
use App\Support\Outcome;
use App\Support\TextNormalizer;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Deck de conceitos')] class extends Component
{
    /** Máximo de resultados mostrados no seletor de uma vez. */
    private const int PICKER_LIMIT = 300;

    #[Locked]
    public int $deckId;

    public ConceptDeckForm $renameForm;

    public bool $showInactive = false;

    /** @var array<int, int|string> */
    public array $selectedCardIds = [];

    public string $pickerSearch = '';

    public string $pickerLetter = '';

    /** @var array<int, int|string> */
    public array $selectedConceptIds = [];

    /**
     * Deck de outro dono (ou apagado) cai em 404 aqui.
     */
    public function mount(int $deck): void
    {
        $model = ConceptDeck::query()
            ->where('access_token_id', $this->tokenId())
            ->findOrFail($deck);

        $this->deckId = $model->id;
        $this->renameForm->name = $model->name;
    }

    private function tokenId(): int
    {
        return (int) session('access_token_id');
    }

    #[Computed]
    public function deck(): ConceptDeck
    {
        return ConceptDeck::query()
            ->where('access_token_id', $this->tokenId())
            ->findOrFail($this->deckId);
    }

    /**
     * @return Collection<int, ConceptDeckCard>
     */
    #[Computed]
    public function cards(): Collection
    {
        return $this->deck->cards()
            ->with('concept')
            ->when(! $this->showInactive, fn ($query) => $query->active())
            ->get()
            ->filter(fn (ConceptDeckCard $card): bool => $card->concept !== null)
            ->sortBy(fn (ConceptDeckCard $card): string => TextNormalizer::fold($card->concept->term))
            ->values();
    }

    #[Computed]
    public function totalCards(): int
    {
        return $this->deck->cards()->count();
    }

    #[Computed]
    public function inactiveCount(): int
    {
        return $this->deck->cards()->where('is_active', false)->count();
    }

    /**
     * @return array<int, int>
     */
    #[Computed]
    public function deckConceptIds(): array
    {
        return $this->deck->cards()->pluck('concept_id')->all();
    }

    #[Computed]
    public function dictionaryHasConcepts(): bool
    {
        return \App\Models\Concepts::query()->exists();
    }

    #[Computed]
    public function pickerIsActive(): bool
    {
        return filled(trim($this->pickerSearch)) || $this->pickerLetter !== '';
    }

    /**
     * @return Collection<int, \App\DTO\ConceptsDTO>
     */
    #[Computed]
    public function pickerResults(): Collection
    {
        $check = match (true) {
            filled(trim($this->pickerSearch)) => app(SearchConcept::class)->handle(trim($this->pickerSearch)),
            $this->pickerLetter !== '' => app(GetConceptsByLetter::class)->handle($this->pickerLetter),
            default => null,
        };

        return $check?->success ? $check->data : collect();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function alphabet(): array
    {
        return range('A', 'Z');
    }

    public function updatedPickerSearch(): void
    {
        $this->pickerSearch = mb_substr($this->pickerSearch, 0, 100);
        $this->pickerLetter = '';
        $this->selectedConceptIds = [];
    }

    public function chooseLetter(string $letter): void
    {
        $this->pickerLetter = in_array($letter, $this->alphabet, true) && $this->pickerLetter !== $letter ? $letter : '';
        $this->pickerSearch = '';
        $this->selectedConceptIds = [];
    }

    public function updatedShowInactive(): void
    {
        $this->selectedCardIds = [];
    }

    public function selectAllListed(): void
    {
        $inDeck = $this->deckConceptIds;

        $this->selectedConceptIds = $this->pickerResults
            ->take(self::PICKER_LIMIT)
            ->pluck('id')
            ->reject(fn (int $id): bool => in_array($id, $inDeck, true))
            ->values()
            ->all();
    }

    public function selectAllCards(): void
    {
        $this->selectedCardIds = $this->cards->pluck('id')->all();
    }

    public function addSelectedConcepts(AddConceptsToDeck $action): void
    {
        $this->report($action->handle($this->deckId, $this->selectedConceptIds, $this->tokenId()));
        $this->selectedConceptIds = [];
        $this->refreshCards();
    }

    public function removeSelectedCards(RemoveCardsFromDeck $action): void
    {
        $this->report($action->handle($this->deckId, $this->selectedCardIds, $this->tokenId()));
        $this->selectedCardIds = [];
        $this->modal('remove-cards')->close();
        $this->refreshCards();
    }

    public function deactivateSelectedCards(SetDeckCardsActiveState $action): void
    {
        $this->report($action->handle($this->deckId, $this->selectedCardIds, false, $this->tokenId()));
        $this->selectedCardIds = [];
        $this->modal('deactivate-cards')->close();
        $this->refreshCards();
    }

    public function reactivateSelectedCards(SetDeckCardsActiveState $action): void
    {
        $this->report($action->handle($this->deckId, $this->selectedCardIds, true, $this->tokenId()));
        $this->selectedCardIds = [];
        $this->refreshCards();
    }

    public function toggleActive(int $cardId, SetDeckCardsActiveState $action): void
    {
        $card = $this->deck->cards()->find($cardId);

        if ($card === null) {
            return;
        }

        $this->report($action->handle($this->deckId, [$cardId], ! $card->is_active, $this->tokenId()), silentOnSuccess: true);
        $this->refreshCards();
    }

    public function toggleStar(int $cardId, ToggleDeckCardStar $action): void
    {
        $this->report($action->handle($cardId, $this->tokenId()), silentOnSuccess: true);
        $this->refreshCards();
    }

    public function renameDeck(RenameConceptDeck $action): void
    {
        $this->renameForm->validate();

        $check = $action->handle($this->deckId, $this->renameForm->name, $this->tokenId());

        if (! $check->success) {
            $this->addError('renameForm.name', $check->message);

            return;
        }

        Flux::toast(duration: 1800, text: $check->message, variant: 'success');
        $this->modal('rename-deck')->close();
        unset($this->deck);
    }

    public function deleteDeck(DeleteConceptDeck $action): void
    {
        $check = $action->handle($this->deckId, $this->tokenId());

        if (! $check->success) {
            Flux::toast(duration: 3000, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');

            return;
        }

        Flux::toast(duration: 1800, text: $check->message, variant: 'success');
        $this->redirect(route('concepts'), navigate: true);
    }

    private function report(Outcome $check, bool $silentOnSuccess = false): void
    {
        if ($check->success && $silentOnSuccess) {
            return;
        }

        match ($check->success) {
            true => Flux::toast(duration: 1800, text: $check->message, variant: 'success'),
            false => Flux::toast(duration: 3000, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };
    }

    private function refreshCards(): void
    {
        unset($this->cards, $this->totalCards, $this->inactiveCount, $this->deckConceptIds);
    }
};
