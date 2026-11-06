<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\MapConcepts;
use App\Support\Outcome;
use App\Support\ReviewSubject;
use Illuminate\Support\Facades\Log;

final readonly class PromoteReadingNoteToCitation
{
    /**
     * Turns a personal reading note into a citation of the same work, keeping the note intact.
     */
    public function handle(int $readingNoteId, int $accessTokenId, string $kind = ReviewSubject::NOTE): Outcome
    {
        try {
            $note = ReviewSubject::owned($kind, $accessTokenId)->findOrFail($readingNoteId);

            if (blank($note->body)) {
                return Outcome::failure(message: 'Escreva a anotação antes de promovê-la a citação.');
            }

            $citation = $note->referenceMaterial->citations()->create([
                'quote_text' => MapConcepts::strip($note->body),
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
