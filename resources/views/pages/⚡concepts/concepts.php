<?php

use App\Actions\{
    GetConceptsByLetter, 
    GetRecentConcepts, 
    SearchConcept,
    AddSoleConcept};
use Illuminate\Support\Collection;
use Livewire\Attributes\{
    Computed, 
    Title, 
    Url};
use App\DTO\SoleConceptDTO;
use Livewire\Component;
use Flux\Flux;

new #[Title('Conceitos')] class extends Component
{
    #[Url(as: 'letra')]
    public ?string $selectedLetter = null;

    #[Url(as: 'busca')]
    public ?string $search = null;

    public ?Collection $conceptsDTO = null;

    public SoleConceptDTO $formConcept;

    public function mount(GetConceptsByLetter $getByLetter, GetRecentConcepts $getRecent, SearchConcept $searchAction): void
    {
        if (! empty($this->search)) {
            $this->searchConcept($searchAction);
        } else {
            $this->loadConcepts($getByLetter, $getRecent);
        }
    }

    public function selectLetter(string $letter, GetConceptsByLetter $getByLetter, GetRecentConcepts $getRecent): void
    {
        $this->selectedLetter = $this->selectedLetter === $letter ? null : $letter;
        $this->search = null;

        $this->loadConcepts($getByLetter, $getRecent);
    }

    public function searchConcept(SearchConcept $action): void
    {
        $this->selectedLetter = null;

        if (empty($this->search)) {
            return;
        }

        $toSearch = $action->handle($this->search);

        $this->conceptsDTO = $toSearch->success ? $toSearch->data : collect();
    }

    public function clearSearch(GetConceptsByLetter $getByLetter, GetRecentConcepts $getRecent): void
    {
        $this->search = null;
        $this->loadConcepts($getByLetter, $getRecent);
    }

    public function loadConcepts(GetConceptsByLetter $getByLetter, GetRecentConcepts $getRecent): void
    {
        if ($this->selectedLetter) {
            $check = $getByLetter->handle($this->selectedLetter);
        } else {
            $check = $getRecent->handle(limit: 3);
        }

        match ($check->success) {
            true => $this->conceptsDTO = $check->data,
            false => $this->conceptsDTO = collect(),
        };
    }

    #[Computed]
    public function alphabet(): array
    {
        return range('A', 'Z');
    }

    public function addSoleConcept(AddSoleConcept $action)
    {
        $this->formConcept->validate();

        $check = $action->handle($this->formConcept);

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        $this->modal('add-concept')->close();

        $this->formConcept->reset();
    }
};
