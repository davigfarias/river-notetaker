<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\AdvicesDTO;
use App\DTO\ConceptsDTO;
use App\DTO\DisciplinesDTO;
use App\DTO\NotesDTO;
use App\DTO\ReferencesDTO;
use App\DTO\TagsDTO;
use App\Models\Concepts;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\PastoralAdvices;
use App\Models\References;
use App\Models\Tags;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Singleton]
readonly class AppRepository
{
    public function createDiscipline(string $title, string $icon): Disciplines
    {
        return Disciplines::create([
            'title' => $title,
            'slug' => Str::slug($title),
            'icon' => $icon,
        ]);
    }

    /**
     * @return Collection<int, DisciplinesDTO>
     */
    public function getDisciplines(): Collection
    {
        return Disciplines::all()
            ->map(fn (Disciplines $discipline): DisciplinesDTO => DisciplinesDTO::fromModel($discipline));
    }

    public function deleteDisciplines(int $id): int
    {
        return Disciplines::where('id', $id)
            ->delete();
    }

    public function getDisciplineBySlug(string $slug): ?DisciplinesDTO
    {
        return Disciplines::search($slug)
            ->get()
            ->map(fn (Disciplines $discipline) => new DisciplinesDTO(
                id: $discipline->id,
                title: $discipline->title,
                icon: $discipline->icon,
            ))
            ->first();
    }

    /**
     * @return Collection<int, DisciplinesDTO>
     */
    public function getAllDisciplines(): ?Collection
    {
        return Disciplines::all()
            ->map(fn (Disciplines $discipline): DisciplinesDTO => DisciplinesDTO::fromModel($discipline));
    }

    public function getNoteById(int $id): ?NotesDTO
    {
        $note = Notes::with(['concepts', 'pastoral_advice', 'references'])->find($id);

        return $note ? NotesDTO::fromModel($note) : null;
    }

    /**
     * @return Collection<int, NotesDTO>
     */
    public function getNotesByDisciplineId(int $disciplineId): Collection
    {
        return Notes::with(['concepts', 'pastoral_advice', 'references'])
            ->where('discipline_id', $disciplineId)
            ->get()
            ->map(fn (Notes $note): NotesDTO => NotesDTO::fromModel($note));
    }

    public function saveNote(NotesDTO $data): Notes
    {
        return Notes::create([
            'discipline_id' => $data->discipline_id,
            'title' => $data->title,
            'tags' => $data->tags,
            'impressions' => $data->impressions,
            'life_experiences' => $data->life_experiences,
        ]);
    }

    public function createConcept(int $noteId, ConceptsDTO $data): Concepts
    {
        return Concepts::create([
            'note_id' => $noteId,
            'term' => $data->term,
            'definition' => $data->definition,
        ]);
    }

    public function checkTermExistence(string $term): bool
    {
        return Concepts::whereRaw('LOWER(term) = ?', [$term])
            ->exists();
    }

    public function createAdvice(int $noteId, AdvicesDTO $data): PastoralAdvices
    {
        return PastoralAdvices::create([
            'note_id' => $noteId,
            'category' => $data->category,
            'advice' => $data->advice,
        ]);
    }

    public function createReference(int $noteId, ReferencesDTO $data): References
    {
        return References::create([
            'note_id' => $noteId,
            'type' => $data->type,
            'reference_text' => $data->reference_text,
        ]);
    }

    /**
     * @return Collection<int, TagsDTO>
     */
    public function getTags(): Collection
    {
        return Tags::all()
            ->map(fn (Tags $tag): TagsDTO => TagsDTO::fromModel($tag));
    }

    public function getRecentConcepts(int $limit = 2): Collection
    {
        return Concepts::latest()
            ->limit($limit)
            ->get()
            ->map(fn (Concepts $concept): ConceptsDTO => ConceptsDTO::fromModel($concept));
    }

    public function getConceptsByLetter(string $letter): Collection
    {
        return Concepts::search('')
            ->query(fn ($builder) => $builder->where('term', 'like', $letter.'%'))
            ->get()
            ->map(fn (Concepts $concept): ConceptsDTO => ConceptsDTO::fromModel($concept));
    }

    public function getAllConcepts(): ?Collection
    {
        return Concepts::all()
            ->map(fn (Concepts $concept) => ConceptsDTO::fromModel($concept));
    }

    public function searchConcepts(string $search): ?Collection
    {
        return Concepts::search($search)
            ->get()
            ->map(fn (Concepts $concept): ConceptsDTO => ConceptsDTO::fromModel($concept));
    }

    public function getAllAdvices() {}
}
