<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Tags;
use Livewire\Wireable;

/**
 * @phpstan-type TagArray array{
 *     id: int|string|null,
 *     title: string|null
 * }
 */
readonly class TagsDTO implements Wireable
{
    public function __construct(
        public int|string $id,
        public string $title,
    ) {}

    /**
     * @return TagArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
        ];
    }

    public static function fromModel(Tags $model): self
    {
        return new self(
            id: $model->id,
            title: $model->title,
        );
    }

    /**
     * @return TagArray
     */
    public function toLivewire(): array
    {
        return $this->toArray();
    }

    /**
     * @param  TagArray  $value
     */
    public static function fromLivewire($value): self
    {
        return new self(
            id: $value['id'] ?? '',
            title: $value['title'] ?? '',
        );
    }
}
