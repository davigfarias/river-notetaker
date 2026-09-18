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
 *     period: int|string|null,
 *     code: string|null,
 *     professor: string|null,
 *     class_weekday: int|string|null,
 *     is_completed: bool,
 * }
 */
class DisciplinesDTO implements Wireable
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $title = null,
        public ?string $icon = null,
        public ?string $slug = null,
        public ?int $period = null,
        public ?string $code = null,
        public ?string $professor = null,
        public ?int $class_weekday = null,
        public bool $is_completed = false,
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
            'period' => $this->period,
            'code' => $this->code,
            'professor' => $this->professor,
            'class_weekday' => $this->class_weekday,
            'is_completed' => $this->is_completed,
        ];
    }

    public static function fromModel(DisciplinesModel $model): self
    {
        return new self(
            id: $model->id,
            title: $model->title,
            icon: $model->icon,
            slug: $model->slug,
            period: $model->period,
            code: $model->code,
            professor: $model->professor,
            class_weekday: $model->class_weekday?->value,
            is_completed: $model->completed_at !== null,
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
            period: ($value['period'] === null || $value['period'] === '') ? null : (int) $value['period'],
            code: $value['code'],
            professor: $value['professor'],
            class_weekday: ($value['class_weekday'] === null || $value['class_weekday'] === '')
                ? null
                : (int) $value['class_weekday'],
            is_completed: (bool) $value['is_completed'],
        );
    }
}
