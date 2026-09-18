<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReadingNote;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class PromoteReadingNoteToCitation
{
    /**
     * Turns a personal reading note into a citation of the same work, keeping the note intact.
     */
    public function handle(int $readingNoteId, int $accessTokenId): Outcome
    {
        try {
            $note = ReadingNote::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($readingNoteId);

            $citation = $note->referenceMaterial->citations()->create([
                'quote_text' => $note->body,
                'location' => $note->location,
                'personal_note' => $note->title,
                'access_token_id' => $accessTokenId,
            ]);

            return Outcome::success(message: 'Anotação promovida a citação.', data: $citation);
        } catch (\Exception $e) {
            Log::error("Erro ao promover anotação a citação: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível promover a anotação.');
        }
    }
}
