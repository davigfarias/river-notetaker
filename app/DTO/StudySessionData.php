<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class StudySessionData
{
    /**
     * @param  array<int, int>  $questionIds
     */
    public function __construct(
        public string $referenceMaterialTitle,
        public string $chapterTitle,
        public array $questionIds,
    ) {}
}
