<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class QuestionData
{
    public function __construct(
        public string $prompt,
        public string $referenceAnswer,
        public ?string $keywords,
        public bool $isCloze = false,
    ) {}
}
