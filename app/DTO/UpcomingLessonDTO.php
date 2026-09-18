<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Próximo encontro de uma disciplina e quantas notas já estarão cobrando
 * revisão quando ele chegar.
 */
final readonly class UpcomingLessonDTO
{
    public function __construct(
        public string $disciplineTitle,
        public string $weekdayLabel,
        public string $date,
        public int $dueCount,
    ) {}

    public function dueLabel(): string
    {
        return $this->dueCount === 1 ? '1 revisão' : "{$this->dueCount} revisões";
    }
}
