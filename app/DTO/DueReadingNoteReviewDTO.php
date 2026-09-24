<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\ReadingNote;
use App\Support\ReviewSchedule;
use Carbon\CarbonImmutable;
use Livewire\Wireable;

/**
 * Um cartão da fila de revisão de anotações de uma referência. Carrega
 * apenas o que o cartão mostra: o corpo da anotação é buscado quando a modal
 * abre.
 *
 * @phpstan-type DueReadingNoteReviewArray array{
 *     id: int,
 *     title: string,
 *     stage: int,
 *     stage_label: string,
 *     due_at: string|null,
 *     days_overdue: int,
 *     tags: array<int, string>,
 * }
 */
class DueReadingNoteReviewDTO implements Wireable
{
    /**
     * @param  array<int, string>  $tags
     */
    public function __construct(
        public int $id,
        public string $title,
        public int $stage,
        public string $stage_label,
        public ?string $due_at = null,
        public int $days_overdue = 0,
        public array $tags = [],
    ) {}

    public function isOverdue(): bool
    {
        return $this->days_overdue > 0;
    }

    public function overdueLabel(): string
    {
        return match (true) {
            $this->days_overdue <= 0 => 'De hoje',
            $this->days_overdue === 1 => 'Atrasada 1 dia',
            default => "Atrasada {$this->days_overdue} dias",
        };
    }

    public static function fromModel(ReadingNote $note, CarbonImmutable $today): self
    {
        $stage = ReviewSchedule::normalizeStage($note->review_stage ?? 0);
        $dueAt = $note->next_review_at;

        return new self(
            id: (int) $note->id,
            title: $note->displayTitle(),
            stage: $stage,
            stage_label: ReviewSchedule::stageLabel($stage),
            due_at: $dueAt?->toDateString(),
            days_overdue: $dueAt instanceof CarbonImmutable
                ? (int) $dueAt->diffInDays($today->startOfDay())
                : 0,
            tags: array_values($note->tags ?? []),
        );
    }

    /**
     * @return DueReadingNoteReviewArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'stage' => $this->stage,
            'stage_label' => $this->stage_label,
            'due_at' => $this->due_at,
            'days_overdue' => $this->days_overdue,
            'tags' => $this->tags,
        ];
    }

    /**
     * @return DueReadingNoteReviewArray
     */
    public function toLivewire(): array
    {
        return $this->toArray();
    }

    /**
     * @param  DueReadingNoteReviewArray  $value
     */
    public static function fromLivewire($value): self
    {
        return new self(
            id: (int) $value['id'],
            title: $value['title'],
            stage: (int) $value['stage'],
            stage_label: $value['stage_label'],
            due_at: $value['due_at'],
            days_overdue: (int) $value['days_overdue'],
            tags: $value['tags'],
        );
    }
}
