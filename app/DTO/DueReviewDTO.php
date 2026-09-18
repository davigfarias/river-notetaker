<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Notes as NotesModel;
use App\Support\ReviewSchedule;
use Carbon\CarbonImmutable;
use Livewire\Wireable;

/**
 * Um cartão da fila de revisão do dia. Carrega apenas o que o cartão mostra: o
 * corpo da nota é buscado quando a modal abre.
 *
 * @phpstan-type DueReviewArray array{
 *     id: int,
 *     title: string,
 *     discipline_id: int,
 *     discipline_title: string,
 *     discipline_icon: string,
 *     stage: int,
 *     stage_label: string,
 *     due_at: string|null,
 *     lessons_overdue: int,
 *     tags: array<int, string>,
 * }
 */
class DueReviewDTO implements Wireable
{
    /**
     * @param  array<int, string>  $tags
     */
    public function __construct(
        public int $id,
        public string $title,
        public int $discipline_id,
        public string $discipline_title,
        public string $discipline_icon,
        public int $stage,
        public string $stage_label,
        public ?string $due_at = null,
        public int $lessons_overdue = 0,
        public array $tags = [],
    ) {}

    public function isOverdue(): bool
    {
        return $this->lessons_overdue > 0;
    }

    public function overdueLabel(): string
    {
        return match (true) {
            $this->lessons_overdue <= 0 => 'Desta aula',
            $this->lessons_overdue === 1 => 'Atrasada 1 aula',
            default => "Atrasada {$this->lessons_overdue} aulas",
        };
    }

    public static function fromModel(NotesModel $note, CarbonImmutable $today): self
    {
        $stage = ReviewSchedule::normalizeStage($note->review_stage ?? 0);
        $dueAt = $note->next_review_at;

        return new self(
            id: (int) $note->id,
            title: (string) $note->title,
            discipline_id: (int) $note->discipline_id,
            discipline_title: $note->discipline->title,
            discipline_icon: $note->discipline->icon,
            stage: $stage,
            stage_label: ReviewSchedule::stageLabel($stage),
            due_at: $dueAt?->toDateString(),
            lessons_overdue: $dueAt instanceof CarbonImmutable
                ? (int) floor($dueAt->diffInWeeks($today->startOfDay()))
                : 0,
            tags: array_values($note->tags ?? []),
        );
    }

    /**
     * @return DueReviewArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'discipline_id' => $this->discipline_id,
            'discipline_title' => $this->discipline_title,
            'discipline_icon' => $this->discipline_icon,
            'stage' => $this->stage,
            'stage_label' => $this->stage_label,
            'due_at' => $this->due_at,
            'lessons_overdue' => $this->lessons_overdue,
            'tags' => $this->tags,
        ];
    }

    /**
     * @return DueReviewArray
     */
    public function toLivewire(): array
    {
        return $this->toArray();
    }

    /**
     * @param  DueReviewArray  $value
     */
    public static function fromLivewire($value): self
    {
        return new self(
            id: (int) $value['id'],
            title: $value['title'],
            discipline_id: (int) $value['discipline_id'],
            discipline_title: $value['discipline_title'],
            discipline_icon: $value['discipline_icon'],
            stage: (int) $value['stage'],
            stage_label: $value['stage_label'],
            due_at: $value['due_at'],
            lessons_overdue: (int) $value['lessons_overdue'],
            tags: $value['tags'],
        );
    }
}
