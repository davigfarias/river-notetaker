<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PrincipleNoteLink;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetNotePrincipleLinks
{
    public function handle(int $noteId): Outcome
    {
        try {
            $data = PrincipleNoteLink::where('note_id', $noteId)
                ->with('principle.principleTopic', 'principle.concept')
                ->get();

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar links da nota: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar os links da nota.', data: collect());
        }
    }
}
