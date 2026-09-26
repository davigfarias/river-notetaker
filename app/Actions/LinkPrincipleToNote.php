<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PrincipleNoteLink;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class LinkPrincipleToNote
{
    public function handle(int $principleId, int $noteId, string $field, string $snippet): Outcome
    {
        try {
            $exists = PrincipleNoteLink::query()
                ->where('principle_id', $principleId)
                ->where('note_id', $noteId)
                ->where('field', $field)
                ->where('snippet', $snippet)
                ->exists();

            if ($exists) {
                return Outcome::failure(message: 'Esse trecho já está linkado a esse princípio.');
            }

            PrincipleNoteLink::create([
                'principle_id' => $principleId,
                'note_id' => $noteId,
                'field' => $field,
                'snippet' => $snippet,
            ]);

            return Outcome::success(message: 'Trecho linkado ao princípio.');
        } catch (\Exception $e) {
            Log::error("Erro ao linkar princípio à nota: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível linkar o trecho.');
        }
    }
}
