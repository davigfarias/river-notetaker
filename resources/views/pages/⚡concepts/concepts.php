<?php

use App\Actions\{
    GenerateConceptDefinition,
    GetConceptsByLetter,
    GetRecentConcepts,
    SearchConcept,
    AddSoleConcept,
    UpdateConcept};
use App\DTO\ConceptsDTO;
use Illuminate\Support\Collection;
use Livewire\Attributes\{Computed, Title, Url};
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

    public SoleConceptDTO $editConceptForm;

    public ?int $editingConceptId = null;

    public bool $editingConcept = false;

    public ?array $aiDefinitions = null;

    public ?string $selectedDefinition = null;

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
        $this->clearAiDefinitions();
    }

    public function generateDefinition(GenerateConceptDefinition $action): void
    {
        $this->reset('aiDefinitions', 'selectedDefinition');

        $check = $action->handle($this->formConcept->term);

        match ($check->success) {
            true => $this->aiDefinitions = $check->data,
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };
    }

    public function updatedSelectedDefinition(?string $value): void
    {
        $this->formConcept->definition = $value ? ($this->aiDefinitions[$value] ?? '') : '';
    }

    public function clearAiDefinitions(): void
    {
        $this->reset('aiDefinitions', 'selectedDefinition');
        $this->formConcept->definition = '';
    }

    public function edit(int $id): void
    {
        $concept = $this->conceptsDTO->firstWhere('id', $id);

        Flux::modals()->close();

        $this->editingConceptId = $id;
        $this->editConceptForm->term = $concept->term;
        $this->editConceptForm->definition = $concept->definition;
        $this->editingConcept = true;
    }

    public function updateConcept(UpdateConcept $action): void
    {
        $this->editConceptForm->validate();

        $check = $action->handle($this->editingConceptId, $this->editConceptForm);

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->conceptsDTO = $this->conceptsDTO->map(
                fn (ConceptsDTO $concept): ConceptsDTO => $concept->id === $this->editingConceptId
                    ? new ConceptsDTO($concept->id, $this->editConceptForm->term, $this->editConceptForm->definition)
                    : $concept
            );

            $this->editingConcept = false;
        }
    }
};
