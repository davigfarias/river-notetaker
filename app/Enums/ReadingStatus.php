<?php

declare(strict_types=1);

namespace App\Enums;

enum ReadingStatus: string
{
    case WantToRead = 'want_to_read';
    case Reading = 'reading';
    case Read = 'read';

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
            self::WantToRead => 'Quero ler',
            self::Reading => 'Lendo',
            self::Read => 'Lido',
        };
    }

    /**
     * Flux badge color for this status.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::WantToRead => 'zinc',
            self::Reading => 'amber',
            self::Read => 'green',
        };
    }
}
