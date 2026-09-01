<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class QuestionResultRow
{
    /**
     * @param  array<int, AnswerSegment>  $answerSegments
     * @param  array<int, ClozeResultSegment>|null  $clozeSegments  non-null for cloze attempts, replacing the diff panels
     */
    public function __construct(
        public int $position,
        public string $prompt,
        public string $referenceAnswer,
        public ?string $answerText,
        public ?int $score,
        public bool $skipped,
        public array $answerSegments,
        public ?array $clozeSegments = null,
    ) {}
}
