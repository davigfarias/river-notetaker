<?php

declare(strict_types=1);

namespace App\Enums;

enum SearchResultType: string
{
    case Nota = 'nota';
    case ConselhoPastoral = 'conselho_pastoral';
    case Conceito = 'conceito';
    case Referencia = 'referencia';
    case Citacao = 'citacao';

    public function label(): string
    {
        return match ($this) {
            self::Nota => 'Notas',
            self::ConselhoPastoral => 'Conselhos Pastorais',
            self::Conceito => 'Conceitos',
            self::Referencia => 'Referências',
            self::Citacao => 'Citações',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Nota => 'document-text',
            self::ConselhoPastoral => 'users',
            self::Conceito => 'light-bulb',
            self::Referencia => 'book-open',
            self::Citacao => 'chat-bubble-left-right',
        };
    }
}
