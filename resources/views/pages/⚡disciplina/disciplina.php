<?php

namespace App\Livewire;

use App\Actions\GetDisciplineNotes as DisciplineNotes;
use App\Actions\GetSingleDisciplineData as DisciplineData;
use App\DTO\DisciplinesDTO;
use App\DTO\NotesDTO;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new #[Title('Disciplinas')] class extends Component
{
    protected DisciplineNotes $disciplineNotes;

    protected DisciplineData $disciplineData;

    public string $disciplineSlug;

    public DisciplinesDTO $disciplineDTO;

    public string $search = '';

    public ?int $selectedNoteId = null;

    public function boot(
        DisciplineData $disciplineData,
        DisciplineNotes $disciplineNotes): void
    {
        $this->disciplineData = $disciplineData;
        $this->disciplineNotes = $disciplineNotes;
    }

    public function mount(string $slug): void
    {
        $this->disciplineSlug = $slug;

        $this->disciplineDTO = $this->disciplineData
            ->handle($slug)
            ->data;

        $firstNote = $this->notes->first();

        if ($firstNote)
        {
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
            $this->disciplineNotes->handle($this->disciplineDTO->id)->data ?? []
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
    }
};
