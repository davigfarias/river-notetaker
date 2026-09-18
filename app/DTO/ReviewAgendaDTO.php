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
    ) {}

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
