<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Notes as NotesModel;
use App\ValueObjects\Date;
use Illuminate\Contracts\Support\Arrayable;
use Livewire\Wireable;

class NotesDTO implements Arrayable, Wireable
{
    /**
     * @param  array<int, string>|null  $tags
     * @param  ConceptsDTO[]|null  $concepts
     * @param  AdvicesDTO[]|null  $pastoral_advice
     * @param  array<int, int>|null  $reference_material_ids
     * @param  array<int, array{id: int, title: string, author: string|null, year: int|null, type: string}>|null  $reference_materials
     */
    public function __construct(
        public int|string|null $id = null,
        public ?int $discipline_id = null,
        public ?int $access_token_id = null,
        public ?string $title = null,
        public ?array $tags = null,
        public ?array $concepts = null,
        public ?string $impressions = null,
        public ?array $pastoral_advice = null,
        public ?string $life_experiences = null,
        public ?string $ai_summary = null,
        public ?array $reference_material_ids = null,
        public ?array $reference_materials = null,
        public ?Date $updated_at = null
    ) {}

    public function date(): ?string
    {
        return $this->updated_at?->format('d M, Y');
    }

    public function day(): ?string
    {
        return $this->updated_at?->format('d M');
    }

    public function year(): ?string
    {
        return $this->updated_at?->format('Y');
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'discipline_id' => $this->discipline_id,
            'access_token_id' => $this->access_token_id,
            'title' => $this->title,
            'tags' => $this->tags,
            'concepts' => $this->concepts
                ? array_map(fn ($c) => $c instanceof ConceptsDTO ? $c->toArray() : (array) $c, $this->concepts)
                : null,
            'pastoral_advice' => $this->pastoral_advice
                ? array_map(fn ($a) => $a instanceof AdvicesDTO ? $a->toArray() : (array) $a, $this->pastoral_advice)
                : null,
            'reference_material_ids' => $this->reference_material_ids
                ? array_values(array_map('intval', $this->reference_material_ids))
                : null,
            'reference_materials' => $this->reference_materials,
            'impressions' => $this->impressions,
            'life_experiences' => $this->life_experiences,
            'ai_summary' => $this->ai_summary,
            'updated_at' => $this->updated_at,
            'date' => $this->date(),
            'day' => $this->day(),
            'year' => $this->year(),
        ];
    }

    public static function fromModel(NotesModel $model): self
    {
        return new self(
            id: $model->id,
            discipline_id: $model->discipline_id,
            access_token_id: $model->access_token_id,
            title: $model->title,
            tags: $model->tags,

            concepts: $model->concepts ? $model->concepts->map(fn ($c) => ConceptsDTO::fromModel($c))->all() : [],
            impressions: $model->impressions,
            pastoral_advice: $model->pastoral_advice ? $model->pastoral_advice->map(fn ($a) => AdvicesDTO::fromModel($a))->all() : [],
            life_experiences: $model->life_experiences,
            ai_summary: $model->ai_summary,
            reference_material_ids: $model->referenceMaterials ? $model->referenceMaterials->pluck('id')->all() : [],
            reference_materials: $model->referenceMaterials
                ? $model->referenceMaterials->map(fn ($material): array => [
                    'id' => $material->id,
                    'title' => $material->title,
                    'author' => $material->author,
                    'year' => $material->year,
                    'type' => $material->type,
                ])->all()
                : [],
            updated_at: $model->updated_at,
        );
    }

    public function toLivewire(): array
    {
        return $this->toArray();
    }

    public static function fromLivewire($value): self
    {
        return new self(
            id: $value['id'] ?? null,
            discipline_id: $value['discipline_id'] ?? null,
            access_token_id: $value['access_token_id'] ?? null,
            title: $value['title'] ?? null,
            tags: $value['tags'] ?? null,

            concepts: isset($value['concepts'])
                ? array_map(fn ($c) => ConceptsDTO::fromLivewire($c), $value['concepts'])
                : null,
            impressions: $value['impressions'] ?? null,

            pastoral_advice: isset($value['pastoral_advice'])
                ? array_map(fn ($a) => AdvicesDTO::fromLivewire($a), $value['pastoral_advice'])
                : null,
            life_experiences: $value['life_experiences'] ?? null,
            ai_summary: $value['ai_summary'] ?? null,

            reference_material_ids: isset($value['reference_material_ids'])
                ? array_values(array_map('intval', $value['reference_material_ids']))
                : null,
            reference_materials: $value['reference_materials'] ?? null,
            updated_at: is_string($value['updated_at'] ?? null)
                ? new Date($value['updated_at'])
                : ($value['updated_at'] ?? null),
        );
    }
}
