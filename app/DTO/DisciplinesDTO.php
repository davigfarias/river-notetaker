<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Disciplines as DisciplinesModel;
use Livewire\Wireable;

/**
 * @phpstan-type DisciplineArray array{
 *     id: int|string|null,
 *     title: string,
 *     icon: string,
 *     slug: string,
 * }
 */
class DisciplinesDTO implements Wireable
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $title = null,
        public ?string $icon = null,
        public ?string $slug = null,
    ) {}

    /**
     * @return DisciplineArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'icon' => $this->icon,
            'slug' => $this->slug,
        ];
    }

    public static function fromModel(DisciplinesModel $model): self
    {
        return new self(
            id: $model->id,
            title: $model->title,
            icon: $model->icon,
            slug: $model->slug,
        );
    }

    /**
     * @return DisciplineArray
     */
    public function toLivewire(): array
    {
        return $this->toArray();
    }

    /**
     * @param  DisciplineArray  $value
     */
    public static function fromLivewire($value): self
    {
        return new self(
            id: $value['id'],
            title: $value['title'],
            icon: $value['icon'],
            slug: $value['slug'],
        );
    }
}
