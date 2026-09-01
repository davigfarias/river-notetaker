<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class AttemptResultData
{
    /**
     * @param  array<int, AnswerSegment>  $referenceSegments
     * @param  array<int, AnswerSegment>  $answerSegments
     */
    public function __construct(
        public int $score,
        public string $referenceAnswer,
        public string $answerText,
        public array $referenceSegments,
        public array $answerSegments,
    ) {}
}
