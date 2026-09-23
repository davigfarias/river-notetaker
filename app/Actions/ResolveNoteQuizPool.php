<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\QuizQuestion;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class ResolveNoteQuizPool
{
    /**
     * Busca as perguntas já cacheadas para o resumo atual da nota. Vazio
     * significa que ainda não foram geradas para esse conteúdo.
     *
     * @return Outcome data: \Illuminate\Support\Collection<int, QuizQuestion>
     */
    public function handle(int $noteId, string $contentHash): Outcome
    {
        try {
            $pool = QuizQuestion::query()
                ->where('note_id', $noteId)
                ->where('content_hash', $contentHash)
                ->get();

            return Outcome::noViewMessage(data: $pool);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível carregar as perguntas desta nota.');
        }
    }
}
