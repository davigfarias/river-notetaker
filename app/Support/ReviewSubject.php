<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Chapter;
use App\Models\ChapterQuizQuestion;
use App\Models\ReadingNote;
use App\Models\ReadingNoteQuizQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * O que a revisão espaçada de uma obra aponta. Livros revisam capítulos;
 * artigos continuam revisando anotações. Os dois têm as mesmas colunas de
 * revisão, então o resto do pipeline só precisa saber qual é qual.
 */
final class ReviewSubject
{
    public const string NOTE = 'note';

    public const string CHAPTER = 'chapter';

    /**
     * @return class-string<Model>
     */
    public static function modelClass(string $kind): string
    {
        return $kind === self::CHAPTER ? Chapter::class : ReadingNote::class;
    }

    /**
     * @return class-string<Model>
     */
    public static function quizClass(string $kind): string
    {
        return $kind === self::CHAPTER ? ChapterQuizQuestion::class : ReadingNoteQuizQuestion::class;
    }

    public static function quizKey(string $kind): string
    {
        return $kind === self::CHAPTER ? 'chapter_id' : 'reading_note_id';
    }

    /**
     * Itens do usuário. Só livros usam capítulo; a anotação antiga de um
     * livro (já copiada para capítulo) fica de fora.
     *
     * @return Builder<Model>
     */
    public static function owned(string $kind, int $accessTokenId): Builder
    {
        return self::modelClass($kind)::query()
            ->whereHas('referenceMaterial', function ($query) use ($kind, $accessTokenId): void {
                $query->where('access_token_id', $accessTokenId)
                    ->where('type', $kind === self::CHAPTER ? '=' : '!=', 'book-open');
            });
    }

    /**
     * Texto que alimenta a MCQ. Vazio quando não há anotação: capítulo sem
     * texto não gera pergunta, mesmo tendo título.
     */
    public static function contentFor(?Model $subject): string
    {
        if (! $subject instanceof Model || blank($subject->getAttribute('body'))) {
            return '';
        }

        $title = $subject->getAttribute('title');

        return trim(($title !== null ? "{$title}\n\n" : '').$subject->getAttribute('body'));
    }
}
