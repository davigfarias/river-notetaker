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
