<?php

declare(strict_types=1);

namespace App\Enums;

enum BloomLevel: string
{
    case Lembrar = 'lembrar';
    case Entender = 'entender';
    case Aplicar = 'aplicar';
    case Analisar = 'analisar';
    case Avaliar = 'avaliar';
    case Criar = 'criar';

    public function label(): string
    {
        return match ($this) {
            self::Lembrar => 'Lembrar',
            self::Entender => 'Entender',
            self::Aplicar => 'Aplicar',
            self::Analisar => 'Analisar',
            self::Avaliar => 'Avaliar',
            self::Criar => 'Criar',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Lembrar => 1,
            self::Entender => 2,
            self::Aplicar => 3,
            self::Analisar => 4,
            self::Avaliar => 5,
            self::Criar => 6,
        };
    }

    public function isHigherOrder(): bool
    {
        return $this->order() >= self::Aplicar->order();
    }
}
