<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Outcome;
use App\Support\ReviewSubject;
use Illuminate\Support\Facades\Log;

final readonly class ResolveReadingNoteQuizPool
{
    /**
     * Busca as perguntas já cacheadas para o conteúdo atual da anotação.
     * Vazio significa que ainda não foram geradas para esse conteúdo.
     *
     * @return Outcome data: \Illuminate\Support\Collection<int, Illuminate\Database\Eloquent\Model>
     */
    public function handle(int $readingNoteId, string $contentHash, string $kind = ReviewSubject::NOTE): Outcome
    {
        try {
            $pool = ReviewSubject::quizClass($kind)::query()
                ->where(ReviewSubject::quizKey($kind), $readingNoteId)
                ->where('content_hash', $contentHash)
                ->get();

            return Outcome::noViewMessage(data: $pool);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível carregar as perguntas desta anotação.');
        }
    }
}
