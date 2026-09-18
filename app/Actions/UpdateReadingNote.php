<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ReadingNoteForm;
use App\Models\ReadingNote;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdateReadingNote
{
    public function handle(int $readingNoteId, ReadingNoteForm $form, int $accessTokenId): Outcome
    {
        try {
            $note = ReadingNote::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($readingNoteId);

            $note->update($form->toAttributes());

            return Outcome::success(message: 'Anotação atualizada.', data: $note);
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar anotação de leitura: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar a anotação.');
        }
    }
}
