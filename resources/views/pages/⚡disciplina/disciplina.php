<?php

namespace App\Livewire;

use App\Actions\AddAdviceToNote;
use App\Actions\AddConceptToNote;
use App\Actions\GetDisciplineNotes as DisciplineNotes;
use App\Actions\GetSingleDisciplineData as DisciplineData;
use App\Actions\GetTags;
use App\Actions\ObserveTerm;
use App\Actions\SubActions\UpdateNote;
use App\Actions\UpdateAdvice;
use App\Actions\UpdateConcept;
use App\DTO\DisciplinesDTO;
use App\DTO\NotesDTO;
use App\DTO\SoleAdviceDTO;
use App\DTO\SoleConceptDTO;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new #[Title('Disciplinas')] class extends Component
{
    protected DisciplineNotes $disciplineNotes;

    protected DisciplineData $disciplineData;

    protected GetTags $getTags;

    public string $disciplineSlug;

    public DisciplinesDTO $disciplineDTO;

    public string $search = '';

    public ?int $selectedNoteId = null;

    public bool $mobileDetail = false;

    public array $draft = [];

    public array $editing = ['title' => false, 'impressions' => false, 'life_experiences' => false];

    public SoleConceptDTO $editConceptForm;

    public ?int $editingConceptId = null;

    public bool $editingConcept = false;

    public SoleAdviceDTO $editAdviceForm;

    public ?int $editingAdviceId = null;

    public bool $editingAdvice = false;

    public SoleConceptDTO $addConceptForm;

    public bool $addingConcept = false;

    public SoleAdviceDTO $addAdviceForm;

    public bool $addingAdvice = false;

    public function boot(
        DisciplineData $disciplineData,
        DisciplineNotes $disciplineNotes,
        GetTags $getTags): void
    {
        $this->disciplineData = $disciplineData;
        $this->disciplineNotes = $disciplineNotes;
        $this->getTags = $getTags;
    }

    public function mount(string $slug): void
    {
        $this->disciplineSlug = $slug;

        $this->disciplineDTO = $this->disciplineData
            ->handle($slug)
            ->data;

        $firstNote = $this->notes->first();

        if ($firstNote) {
            $this->selectedNoteId = $firstNote->id;
        }
    }

    /**
     * @return Collection<int, NotesDTO>
     */
    #[Computed]
    public function notes(): Collection
    {
        return collect(
            $this->disciplineNotes->handle($this->disciplineDTO->id, (int) session('access_token_id'))->data ?? []
        );
    }

    #[Computed]
    public function selectedNote(): ?NotesDTO
    {
        return $this->notes->firstWhere('id', $this->selectedNoteId) ?? $this->notes->first();
    }

    public function selectNote(int $id): void
    {
        $this->selectedNoteId = $id;
        $this->mobileDetail = true;
    }

    #[Computed]
    public function allTags(): Collection
    {
        return $this->getTags->handle()->data;
    }

    public function edit(string $field): void
    {
        $this->draft[$field] = $this->selectedNote->{$field};
        $this->editing[$field] = true;
    }

    public function updateNote(UpdateNote $action, string $field): void
    {
        if ($field === 'title') {
            $this->validate(['draft.title' => 'required|string|min:3|max:255']);
        }

        $outcome = $action->handle($this->selectedNote->id, (int) session('access_token_id'), [$field => $this->draft[$field]]);

        match ($outcome->success) {
            true => Flux::toast(text: $outcome->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger'),
        };

        if ($outcome->success) {
            $this->editing[$field] = false;
            unset($this->notes, $this->selectedNote);
        }
    }

    public function toggleTag(string $title, UpdateNote $action): void
    {
        $tags = collect($this->selectedNote->tags);

        $this->draft['tags'] = $tags->contains($title)
            ? $tags->reject($title)->values()->all()
            : $tags->push($title)->all();

        $this->updateNote($action, 'tags');
    }

    public function editConcept(int $id): void
    {
        $concept = collect($this->selectedNote->concepts)->firstWhere('id', $id);

        $this->editingConceptId = $id;
        $this->editConceptForm->term = $concept->term;
        $this->editConceptForm->definition = $concept->definition;
        $this->editingConcept = true;
    }

    public function updateConcept(UpdateConcept $action): void
    {
        $this->editConceptForm->validate();

        $outcome = $action->handle($this->editingConceptId, $this->editConceptForm);

        match ($outcome->success) {
            true => Flux::toast(text: $outcome->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger'),
        };

        if ($outcome->success) {
            $this->editingConcept = false;
            unset($this->notes, $this->selectedNote);
        }
    }

    public function editAdvice(int $id): void
    {
        $advice = collect($this->selectedNote->pastoral_advice)->firstWhere('id', $id);

        $this->editingAdviceId = $id;
        $this->editAdviceForm->category = $advice->category;
        $this->editAdviceForm->advice = $advice->advice;
        $this->editingAdvice = true;
    }

    public function updateAdvice(UpdateAdvice $action): void
    {
        $this->editAdviceForm->validate();

        $outcome = $action->handle($this->editingAdviceId, $this->editAdviceForm);

        match ($outcome->success) {
            true => Flux::toast(text: $outcome->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger'),
        };

        if ($outcome->success) {
            $this->editingAdvice = false;
            unset($this->notes, $this->selectedNote);
        }
    }

    public function verifyConceptExistence(ObserveTerm $action): void
    {
        $check = $action->handle(trim($this->addConceptForm->term));

        if ($check->data) {
            Flux::toast(text: 'O conceito já está registrado no sistema!', variant: 'alert');
        }
    }

    public function addConcept(AddConceptToNote $action): void
    {
        $this->addConceptForm->validate();

        $outcome = $action->handle($this->selectedNote->id, $this->addConceptForm);

        match ($outcome->success) {
            true => Flux::toast(text: $outcome->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger'),
        };

        if ($outcome->success) {
            $this->addingConcept = false;
            $this->addConceptForm->reset();
            unset($this->notes, $this->selectedNote);
        }
    }

    public function addAdvice(AddAdviceToNote $action): void
    {
        $this->addAdviceForm->validate();

        $outcome = $action->handle($this->selectedNote->id, $this->addAdviceForm);

        match ($outcome->success) {
            true => Flux::toast(text: $outcome->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $outcome->message, variant: 'danger'),
        };

        if ($outcome->success) {
            $this->addingAdvice = false;
            $this->addAdviceForm->reset();
            unset($this->notes, $this->selectedNote);
        }
    }
};
