<?php

declare(strict_types=1);

namespace App\Enums;

enum ReferencesIcon: string
{
    case BookOpen = 'book-open';
    case Newspaper = 'newspaper';
    case VideoCamera = 'video-camera';
    case Film = 'film';
    case Music = 'music';
    case Series = 'computer-desktop';

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
            self::BookOpen => 'Livro',
            self::Newspaper => 'Artigo',
            self::VideoCamera => 'Vídeo',
            self::Film => 'Filme',
            self::Music => 'Música',
            self::Series => 'Série',
        };
    }

    /**
     * Valid Heroicon name for this type (the backing value is not always one).
     */
    public function icon(): string
    {
        return match ($this) {
            self::BookOpen => 'book-open',
            self::Newspaper => 'newspaper',
            self::VideoCamera => 'video-camera',
            self::Film => 'film',
            self::Music => 'musical-note',
            self::Series => 'computer-desktop',
        };
    }

    public static function fromLabel(string $label): self
    {
        return match ($label) {
            'Livro' => self::BookOpen,
            'Artigo' => self::Newspaper,
            'Vídeo' => self::VideoCamera,
            'Filme' => self::Film,
            'Música' => self::Music,
            'Série' => self::Series,
        };
    }
}
