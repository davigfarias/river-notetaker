<?php

use App\Actions\{
    AddSoleConcept,
    GenerateConceptDefinition,
    GetConceptsByLetter,
    GetRecentConcepts,
    LinkConcepts,
    SearchConcept,
    SearchLinkableConcepts,
    UnlinkConcepts,
    UpdateConcept};
use App\DTO\SoleConceptDTO;
use App\Models\Concepts;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\{Computed, Lazy, Title, Url};
use Livewire\Component;
use Flux\Flux;

new #[Title('Conceitos')] #[Lazy] class extends Component
{
    #[Url(as: 'letra')]
    public ?string $selectedLetter = null;

    #[Url(as: 'busca')]
    public string $search = '';

    public SoleConceptDTO $formConcept;

    public SoleConceptDTO $editConceptForm;

    public ?int $editingConceptId = null;

    public bool $editingConcept = false;

    /** @var array{definition_a: string, definition_b: string}|null */
    public ?array $aiDefinitions = null;

    public ?string $selectedDefinition = null;

    public string $relatedSearch = '';

    /**
     * @return Collection<int, \App\DTO\ConceptsDTO>
     */
    #[Computed]
    public function concepts(): Collection
    {
        $check = match (true) {
            filled($this->search) => app(SearchConcept::class)->handle($this->search),
            $this->selectedLetter !== null => app(GetConceptsByLetter::class)->handle($this->selectedLetter),
            default => app(GetRecentConcepts::class)->handle(limit: 3),
        };

        return $check->success ? $check->data : collect();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function alphabet(): array
    {
        return range('A', 'Z');
    }

    /**
     * @return array{nodes: array<int, array{id: int, label: string}>, edges: array<int, array{from: int, to: int}>}
     */
    #[Computed]
    public function graphData(): array
    {
        $nodes = Concepts::all(['id', 'term'])
            ->map(fn (Concepts $concept): array => [
                'id' => $concept->id,
                'label' => $concept->term,
            ])
            ->values()
            ->all();

        $edges = DB::table('concept_concept')
            ->whereColumn('concept_id', '<', 'related_concept_id')
            ->get(['concept_id as from', 'related_concept_id as to'])
            ->map(fn ($row): array => ['from' => $row->from, 'to' => $row->to])
            ->values()
            ->all();

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * @return Collection<int, Concepts>
     */
    #[Computed]
    public function linkedConcepts(): Collection
    {
        if (! $this->editingConceptId) {
            return collect();
        }

        return Concepts::find($this->editingConceptId)->relatedConcepts;
    }

    /**
     * @return Collection<int, \App\DTO\ConceptsDTO>
     */
    #[Computed]
    public function linkableResults(): Collection
    {
        if (! $this->editingConceptId || trim($this->relatedSearch) === '') {
            return collect();
        }

        $excludeIds = [$this->editingConceptId, ...$this->linkedConcepts->pluck('id')->all()];

        return app(SearchLinkableConcepts::class)->handle($this->relatedSearch, $excludeIds)->data;
    }

    public function linkConcept(int $relatedId, LinkConcepts $action): void
    {
        $check = $action->handle($this->editingConceptId, $relatedId);

        if (! $check->success) {
            Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');

            return;
        }

        unset($this->linkableResults, $this->linkedConcepts, $this->graphData);

        $this->dispatch('graph-updated', graph: $this->graphData);
    }

    public function unlinkConcept(int $relatedId, UnlinkConcepts $action): void
    {
        $action->handle($this->editingConceptId, $relatedId);

        unset($this->linkedConcepts, $this->graphData);

        $this->dispatch('graph-updated', graph: $this->graphData);
    }

    public function updatedSearch(): void
    {
        if (filled($this->search)) {
            $this->selectedLetter = null;
        }
    }

    public function selectLetter(string $letter): void
    {
        $this->selectedLetter = $this->selectedLetter === $letter ? null : $letter;
        $this->search = '';
    }

    public function addSoleConcept(AddSoleConcept $action): void
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

        unset($this->concepts, $this->graphData);
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
        $concept = $this->concepts->firstWhere('id', $id);

        if (! $concept) {
            return;
        }

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
            unset($this->concepts, $this->graphData);

            $this->editingConcept = false;
        }
    }
};
