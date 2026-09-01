<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class AnswerDiff
{
    /**
     * @param  array<int, AnswerSegment>  $referenceSegments
     * @param  array<int, AnswerSegment>  $answerSegments
     */
    public function __construct(
        public array $referenceSegments,
        public array $answerSegments,
    ) {}
}
