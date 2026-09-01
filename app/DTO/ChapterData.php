<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ChapterData
{
    public function __construct(
        public string $title,
    ) {}
}
