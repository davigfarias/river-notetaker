<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ChapterResultsData
{
    /**
     * @param  array<int, QuestionResultRow>  $rows
     */
    public function __construct(
        public string $chapterTitle,
        public int $averageScore,
        public int $questionCount,
        public array $rows,
    ) {}
}
