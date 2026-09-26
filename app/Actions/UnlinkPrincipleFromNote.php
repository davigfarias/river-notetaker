<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PrincipleNoteLink;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UnlinkPrincipleFromNote
{
    public function handle(int $linkId): Outcome
    {
        try {
            PrincipleNoteLink::destroy($linkId);

            return Outcome::success(message: 'Link removido.');
        } catch (\Exception $e) {
            Log::error("Erro ao remover link: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível remover o link.');
        }
    }
}
