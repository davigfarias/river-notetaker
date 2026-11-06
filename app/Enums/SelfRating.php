<?php

declare(strict_types=1);

namespace App\Enums;

enum SelfRating: string
{
    case Again = 'again';
    case Hard = 'hard';
    case Good = 'good';
    case Easy = 'easy';

    public function label(): string
    {
        return match ($this) {
            self::Again => 'Errei',
            self::Hard => 'Difícil',
            self::Good => 'Bom',
            self::Easy => 'Fácil',
        };
    }
}
