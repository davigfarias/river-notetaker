<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Support\Collection;

/**
 * A fila de revisão de anotações de leitura de uma referência: o que cobrar
 * agora e quanto ficou de fora do teto.
 */
final readonly class ReferenceReviewAgendaDTO
{
    /**
     * @param  Collection<int, DueReadingNoteReviewDTO>  $due
     */
    public function __construct(
        public Collection $due,
        public int $totalDue = 0,
    ) {}

    public function hiddenCount(): int
    {
        return max(0, $this->totalDue - $this->due->count());
    }

    public function isEmpty(): bool
    {
        return $this->due->isEmpty();
    }
}
