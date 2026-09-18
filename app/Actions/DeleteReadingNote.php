<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReadingNote;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class DeleteReadingNote
{
    public function handle(int $readingNoteId, int $accessTokenId): Outcome
    {
        try {
            $note = ReadingNote::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($readingNoteId);

            $note->delete();

            return Outcome::success(message: 'Anotação removida.');
        } catch (\Exception $e) {
            Log::error("Erro ao remover anotação de leitura: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível remover a anotação.');
        }
    }
}
