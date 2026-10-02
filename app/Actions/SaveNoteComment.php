<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\NoteComment;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class SaveNoteComment
{
    /**
     * Cria um comentário novo quando $commentId é null; senão, edita só o corpo.
     */
    public function handle(?int $commentId, int $noteId, string $field, string $snippet, string $body): Outcome
    {
        try {
            if ($commentId === null) {
                NoteComment::create(['note_id' => $noteId, 'field' => $field, 'snippet' => $snippet, 'body' => $body]);

                return Outcome::success(message: 'Comentário adicionado.');
            }

            NoteComment::where('note_id', $noteId)->findOrFail($commentId)->update(['body' => $body]);

            return Outcome::success(message: 'Comentário atualizado.');
        } catch (\Exception $e) {
            Log::error("Erro ao salvar comentário da nota: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível salvar o comentário.');
        }
    }
}
