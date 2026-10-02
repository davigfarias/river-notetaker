<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\NoteComment;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class DeleteNoteComment
{
    public function handle(int $commentId, int $noteId): Outcome
    {
        try {
            NoteComment::where('note_id', $noteId)->whereKey($commentId)->delete();

            return Outcome::success(message: 'Comentário removido.');
        } catch (\Exception $e) {
            Log::error("Erro ao remover comentário: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível remover o comentário.');
        }
    }
}
