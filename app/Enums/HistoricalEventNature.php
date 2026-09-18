<?php

declare(strict_types=1);

namespace App\Enums;

enum HistoricalEventNature: string
{
    case Event = 'event';
    case Book = 'book';
    case Article = 'article';
    case Person = 'person';
    case Document = 'document';
    case Artwork = 'artwork';

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
            self::Event => 'Evento histórico',
            self::Book => 'Livro',
            self::Article => 'Artigo',
            self::Person => 'Pessoa',
            self::Document => 'Documento',
            self::Artwork => 'Obra de arte',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Event => 'flag',
            self::Book => 'book-open',
            self::Article => 'newspaper',
            self::Person => 'user',
            self::Document => 'document-text',
            self::Artwork => 'paint-brush',
        };
    }

    /**
     * Timeline indicator and badge color for this nature.
     */
    public function color(): string
    {
        return match ($this) {
            self::Event => 'amber',
            self::Book => 'sky',
            self::Article => 'teal',
            self::Person => 'violet',
            self::Document => 'lime',
            self::Artwork => 'rose',
        };
    }
}
