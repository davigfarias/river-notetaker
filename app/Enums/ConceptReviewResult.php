<?php

declare(strict_types=1);

namespace App\Enums;

enum ConceptReviewResult: string
{
    case Remembered = 'remembered';
    case Forgot = 'forgot';

    public function remembered(): bool
    {
        return $this === self::Remembered;
    }
}
