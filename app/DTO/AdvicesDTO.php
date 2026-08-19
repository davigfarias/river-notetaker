<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\PastoralAdvices;
use Livewire\Wireable;

class AdvicesDTO implements Wireable
{
    public function __construct(
        public ?int $id = null,
        public ?int $note_id = null,
        public ?string $category = null,
        public ?string $advice = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'note_id' => $this->note_id,
            'category' => $this->category,
            'advice' => $this->advice,
        ];
    }

    public function toLivewire(): array
    {
        return $this->toArray();
    }

    public static function fromLivewire($value): self
    {
        return new self(
            id: $value['id'] ?? null,
            note_id: $value['note_id'] ?? null,
            category: $value['category'] ?? null,
            advice: $value['advice'] ?? null,
        );
    }

    public static function fromModel(PastoralAdvices $model): self
    {
        return new self(
            id: $model->id,
            note_id: $model->note_id,
            category: $model->category,
            advice: $model->advice,
        );
    }
}
