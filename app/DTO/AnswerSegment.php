<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class AnswerSegment
{
    public function __construct(
        public bool $matched,
        public string $text,
    ) {}
}
