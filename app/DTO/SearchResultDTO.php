<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\SearchResultType;
use App\Models\Citation;
use App\Models\Concepts;
use App\Models\Notes;
use App\Models\PastoralAdvices;
use App\Models\ReferenceMaterial;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Livewire\Wireable;

/**
 * @phpstan-type SearchResultArray array{
 *     type: string,
 *     id: int,
 *     title: string,
 *     snippet: string|null,
 *     url: string
 * }
 */
class SearchResultDTO implements Arrayable, Wireable
{
    public function __construct(
        public ?SearchResultType $type = null,
        public ?int $id = null,
        public ?string $title = null,
        public ?string $snippet = null,
        public ?string $url = null,
    ) {}

    /**
     * @return SearchResultArray
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type?->value,
            'id' => $this->id,
            'title' => $this->title,
            'snippet' => $this->snippet,
            'url' => $this->url,
        ];
    }

    /**
     * @return SearchResultArray
     */
    public function toLivewire(): array
    {
        return $this->toArray();
    }

    /**
     * @param  SearchResultArray  $value
     */
    public static function fromLivewire($value): self
    {
        return new self(
            type: isset($value['type']) ? SearchResultType::from($value['type']) : null,
            id: isset($value['id']) ? (int) $value['id'] : null,
            title: $value['title'] ?? null,
            snippet: $value['snippet'] ?? null,
            url: $value['url'] ?? null,
        );
    }

    public static function fromNote(Notes $note): self
    {
        return new self(
            type: SearchResultType::Nota,
            id: $note->id,
            title: $note->title,
            snippet: $note->impressions ? Str::limit($note->impressions, 140) : null,
            url: route('disciplinas.show', ['slug' => $note->discipline->slug, 'nota' => $note->id]),
        );
    }

    public static function fromAdvice(PastoralAdvices $advice, string $term): self
    {
        return new self(
            type: SearchResultType::ConselhoPastoral,
            id: $advice->id,
            title: $advice->category,
            snippet: Str::limit($advice->advice, 140),
            url: route('pastoral', ['busca' => $term]),
        );
    }

    public static function fromConcept(Concepts $concept, string $term): self
    {
        return new self(
            type: SearchResultType::Conceito,
            id: $concept->id,
            title: $concept->term,
            snippet: Str::limit($concept->definition, 140),
            url: route('concepts', ['busca' => $term]),
        );
    }

    public static function fromReference(ReferenceMaterial $reference): self
    {
        return new self(
            type: SearchResultType::Referencia,
            id: $reference->id,
            title: $reference->title,
            snippet: $reference->author,
            url: route('referencias.show', $reference->id),
        );
    }

    public static function fromCitation(Citation $citation): self
    {
        return new self(
            type: SearchResultType::Citacao,
            id: $citation->id,
            title: $citation->referenceMaterial->title,
            snippet: Str::limit($citation->quote_text, 140),
            url: route('referencias.show', $citation->reference_material_id),
        );
    }
}
