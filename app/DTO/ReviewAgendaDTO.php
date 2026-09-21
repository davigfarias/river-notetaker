<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\Weekday;
use Illuminate\Support\Collection;

/**
 * A fila de revisão do dia inteira: o que cobrar agora, quanto ficou de fora do
 * teto e, quando hoje não é dia de aula, qual é o próximo encontro.
 */
final readonly class ReviewAgendaDTO
{
    /**
     * @param  Collection<int, DueReviewDTO>  $due
     * @param  Collection<int, UpcomingLessonDTO>  $upcoming
     */
    public function __construct(
        public Weekday $today,
        public Collection $due,
        public int $totalDue = 0,
        public Collection $upcoming = new Collection,
        public bool $hasLessonToday = false,
        public int $awaitingSummaryCount = 0,
    ) {}

    /**
     * Notas que ficaram de fora da fila por ainda não terem resumo escrito.
     */
    public function hasNotesAwaitingSummary(): bool
    {
        return $this->awaitingSummaryCount > 0;
    }

    public function awaitingSummaryLabel(): string
    {
        return $this->awaitingSummaryCount === 1
            ? '1 nota sem resumo'
            : "{$this->awaitingSummaryCount} notas sem resumo";
    }

    public function hiddenCount(): int
    {
        return max(0, $this->totalDue - $this->due->count());
    }

    public function isEmpty(): bool
    {
        return $this->due->isEmpty();
    }

    /**
     * @return Collection<int, string>
     */
    public function disciplinesInQueue(): Collection
    {
        return $this->due
            ->pluck('discipline_title')
            ->unique()
            ->values();
    }
}
