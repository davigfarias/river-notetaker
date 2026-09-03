<?php

use App\DTO\{AdvicesDTO, ConceptsDTO, NotesDTO, ReferenceMaterialForm, SoleConceptDTO};
use App\Actions\{AddReferenceMaterial, GetAllDisciplines, GetTags, ObserveTerm};
use App\Actions\{SearchReferenceMaterials, UpdateConcept};
use Livewire\Attributes\{Computed, On, Title};
use App\Actions\Orchestrators\SaveNote;
use Illuminate\Support\Collection;
use App\Models\ReferenceMaterial;
use App\Models\Concepts;
use Livewire\Component;
use Flux\Flux;

new #[Title('Criar uma Nova Nota')] class extends Component
{
    #TODO: Refactor this. Modals should not work like this in Flux;
    public bool $showDeleteModal = false;

    public NotesDTO $notes;

    public SoleConceptDTO $editConceptForm;

    public ?int $editingConceptId = null;

    public bool $editingConcept = false;

    public string $refSearch = '';

    public ReferenceMaterialForm $refForm;

    #TODO: write this as a Form Object. 
    protected function rules(): array
    {
        return [
            'notes.title' => 'required|string|min:3|max:255',
            'notes.discipline_id' => 'required|numeric',
            'notes.tags' => 'nullable|array',
            'notes.concepts' => 'nullable|array',
            'notes.impressions' => 'nullable|string',
            'notes.pastoral_advice' => 'nullable|array',
            'notes.life_experiences' => 'nullable|string',
            'notes.reference_material_ids' => 'nullable|array',
        ];
    }

    #TODO: write a Form Object
    protected function messages(): array
    {
        return [
            'notes.title.required' => 'É necessário um título para sua nota',
            'notes.discipline_id.required' => 'Selecione uma disciplina',
        ];
    }

    public function mount(NotesDTO $notes)
    {
        $this->notes = clone $notes;
    }

    public function boot(
        GetAllDisciplines $getAllDisciplinesAction,
        GetTags $getTagsAction): void
    {
        $this->disciplines($getAllDisciplinesAction);
        $this->tags($getTagsAction);
    }

    #[Computed]
    public function disciplines(GetAllDisciplines $action)
    {
        $check = $action->handle();

        match ($check->success) {
            true => $this->disciplines = $check->data,
            false => $this->disciplines = null
        };
    }

    public function addConcept(): void
    {
        $this->notes->concepts[] = new ConceptsDTO;
    }

    public function removeConcept(int $index): void
    {
        unset($this->notes->concepts[$index]);
        $this->notes->concepts = array_values($this->notes->concepts);
    }

    public function verifyExistence(
        ObserveTerm $action,
        string $index)
    {
        $typed = $this->notes->concepts[$index] ?? null;

        if (! $typed) {
            return;
        }

        $check = $action
            ->handle(trim($typed->term));

        if ($check->data) {
            Flux::toast(
                text: 'O conceito já está registrado no sistema!',
                variant: 'alert',
                link: [
                    'text' => 'Editar conceito existente',
                    'href' => '#edit-concept-'.$check->data->id,
                    'navigate' => false,
                ],
            );
        }
    }

    #[On('edit-concept-requested')]
    public function openConceptEdit(int $id): void
    {
        $concept = Concepts::find($id);

        if (! $concept) {
            return;
        }

        $this->editingConceptId = $concept->id;
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
            $this->editingConcept = false;
        }
    }

    public function addPastoralAdvice(): void
    {
        $this->notes->pastoral_advice[] = new AdvicesDTO;
    }

    public function removePastoralAdvice(int $index): void
    {
        unset($this->notes->pastoral_advice[$index]);
        $this->notes->pastoral_advice = array_values($this->notes->pastoral_advice);
    }

    #[Computed]
    public function referenceResults(): Collection
    {
        if (trim($this->refSearch) === '') {
            return collect();
        }

        $linked = $this->notes->reference_material_ids ?? [];

        return app(SearchReferenceMaterials::class)->handle($this->refSearch, (int) session('access_token_id'), 8)
            ->data
            ->reject(fn (ReferenceMaterial $material): bool => in_array($material->id, $linked, true))
            ->values();
    }

    #[Computed]
    public function linkedReferences(): Collection
    {
        $ids = $this->notes->reference_material_ids ?? [];

        if ($ids === []) {
            return collect();
        }

        return ReferenceMaterial::query()
            ->where('access_token_id', (int) session('access_token_id'))
            ->whereIn('id', $ids)
            ->get();
    }

    public function linkReference(int $id): void
    {
        $ids = $this->notes->reference_material_ids ?? [];

        if (! in_array($id, $ids, true)) {
            $ids[] = $id;
            $this->notes->reference_material_ids = $ids;
        }

        $this->refSearch = '';
        unset($this->referenceResults, $this->linkedReferences);
    }

    public function unlinkReference(int $id): void
    {
        $this->notes->reference_material_ids = array_values(
            array_filter($this->notes->reference_material_ids ?? [], fn (int $existing): bool => $existing !== $id)
        );

        unset($this->referenceResults, $this->linkedReferences);
    }

    public function addNewReference(AddReferenceMaterial $action): void
    {
        $this->refForm->validate();

        $check = $action->handle($this->refForm, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->linkReference($check->data->id);
            $this->modal('add-reference-material')->close();
            $this->refForm->reset();
        }
    }

    private function filterConcepts(): array
    {
        $concepts = array_filter($this->notes->concepts ?? [], function ($concept) {
            return ! empty(trim($concept->term ?? ''))
                && ! empty(trim($concept->definition ?? ''));
        });

        return array_values($concepts);
    }

    private function filterPastoralAdvice(): array
    {
        $advices = array_filter($this->notes->pastoral_advice ?? [], function ($pastoral) {
            return ! empty(trim($pastoral->category ?? ''))
                && ! empty(trim($pastoral->advice ?? ''));
        });

        return array_values($advices);
    }

    #[Computed]
    public function tags(GetTags $action)
    {
        $check = $action->handle();

        match ($check->success) {
            true => $this->tags = $check->data,
            false => $this->tags = null,
        };
    }

    public function addTag(string $title): void
    {
        $tags = collect($this->notes->tags);

        $this->notes->tags = $tags->contains($title)
            ? $tags->reject($title)->values()->all()
            : $tags->push($title)->all();
    }

    public function save(SaveNote $action): void
    {
        $this->validate($this->rules(), $this->messages());

        $this->notes->concepts = $this->filterConcepts();
        $this->notes->pastoral_advice = $this->filterPastoralAdvice();
        $this->notes->access_token_id = (int) session('access_token_id');

        $outcome = $action->handle($this->notes);

        if ($outcome->success) {
            Flux::toast(text: 'Anotação salva com sucesso.', variant: 'success');
            $this->dispatch('note-draft-saved');
            $this->redirectRoute('dashboard', navigate: true);
        } else {
            Flux::toast(text: $outcome->message, variant: 'danger');
        }
    }
};
