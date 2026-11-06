<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\ExerciseType;

/**
 * Um item planejado da sessão. `retry` marca a reaparição única de um "Errei".
 */
final readonly class SessionItem
{
    public function __construct(
        public int $principleId,
        public ExerciseType $type,
        public string $groupKey = '',
        public bool $retry = false,
    ) {}

    /**
     * @return array{principle_id: int, type: string, retry: bool}
     */
    public function toComposition(): array
    {
        return ['principle_id' => $this->principleId, 'type' => $this->type->value, 'retry' => $this->retry];
    }
}
