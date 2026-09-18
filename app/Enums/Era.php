<?php

declare(strict_types=1);

namespace App\Enums;

enum Era: string
{
    case BeforeChrist = 'bc';
    case AnnoDomini = 'ad';

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::BeforeChrist => 'a.C.',
            self::AnnoDomini => 'd.C.',
        };
    }
}
