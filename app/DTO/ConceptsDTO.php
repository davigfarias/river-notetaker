<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Concepts;
use Livewire\Wireable;

/**
 * @phpstan-type ConceptArray array{
 *     id: int,
 *     term: string,
 *     definition: string
 * }
 */
class ConceptsDTO implements Wireable
{
    public function __construct(
        public ?int $id = null,
        public ?string $term = null,
        public ?string $definition = null,
    ) {}

    /**
     * @return ConceptArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'term' => $this->term,
            'definition' => $this->definition,
        ];
    }

    /**
     * @return ConceptArray
     */
    public function toLivewire(): array
    {
        return $this->toArray();
    }

    /**
     * @param  ConceptArray  $value
     */
    public static function fromLivewire($value): self
    {
        return new self(
            id: isset($value['id'])
                ? (int) $value['id']
                : null,
            term: $value['term'] ?? null,
            definition: $value['definition'] ?? null,
        );
    }

    public static function fromModel(Concepts $model): self
    {
        return new self(
            id: $model->id,
            term: $model->term,
            definition: $model->definition,
        );
    }

    public static function fromArray(array $concept): self
    {
        return new self(
            id: $concept['id'],
            term: $concept['term'],
            definition: $concept['definition'],
        );
    }
}
