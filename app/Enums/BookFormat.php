<?php

declare(strict_types=1);

namespace App\Enums;

enum BookFormat: string
{
    case Physical = 'physical';
    case Kindle = 'kindle';
    case AppleBooks = 'apple_books';

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
            self::Physical => 'Físico',
            self::Kindle => 'Kindle',
            self::AppleBooks => 'Apple Books',
        };
    }

    public function isDigital(): bool
    {
        return $this !== self::Physical;
    }
}
