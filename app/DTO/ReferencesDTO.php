<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\References;
use Livewire\Wireable;

/**
 * @phpstan-type ReferencesArray array{
 *     note_id: int,
 *     type: string,
 *     reference_text: string
 * }
 */
class ReferencesDTO implements Wireable
{
    public function __construct(
        public ?int $note_id = null,
        public ?string $type = null,
        public ?string $reference_text = null,
    ) {}

    /**
     * @return ReferencesArray
     */
    public function toArray(): array
    {
        return [
            'note_id' => $this->note_id,
            'type' => $this->type,
            'reference_text' => $this->reference_text,
        ];
    }

    /**
     * @return ReferencesArray
     */
    public function toLivewire(): array
    {
        return $this->toArray();
    }

    public static function fromLivewire($value): self
    {
        return new self(
            note_id: $value['note_id'] ?? null,
            type: $value['type'] ?? null,
            reference_text: $value['reference_text'] ?? null,
        );
    }

    public static function fromArray(array $concepts): self
    {
        return new self(
            note_id: $concepts['note_id'],
            type: $concepts['type'],
            reference_text: $concepts['reference_text'],
        );
    }

    public static function fromModel(References $model): self
    {
        return new self(
            note_id: $model->note_id,
            type: $model->type,
            reference_text: $model->reference_text,
        );
    }
}
