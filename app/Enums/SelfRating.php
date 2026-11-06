<?php

declare(strict_types=1);

namespace App\Enums;

enum SelfRating: string
{
    case Again = 'again';
    case Hard = 'hard';
    case Good = 'good';
    case Easy = 'easy';

    /** Ordem de generosidade: errei < difícil < bom < fácil. Usada para comparar IA e usuário. */
    public function score(): int
    {
        return match ($this) {
            self::Again => 0,
            self::Hard => 1,
            self::Good => 2,
            self::Easy => 3,
        };
    }

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
