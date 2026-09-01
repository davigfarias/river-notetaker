<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ClozeResultSegment
{
    public function __construct(
        public bool $blank,
        public string $text,
        public ?string $given = null,
        public ?string $expected = null,
        public bool $correct = false,
    ) {}
}
