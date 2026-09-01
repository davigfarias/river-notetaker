<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Support\Carbon;

final readonly class BookProgress
{
    public function __construct(
        public int $totalQuestions,
        public int $attemptedQuestions,
        public int $percent,
        public ?Carbon $lastStudiedAt,
    ) {}
}
