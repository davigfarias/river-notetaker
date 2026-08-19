<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\Models\Notes;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdateNote
{
    public function handle(int $id, int $accessTokenId, array $data): Outcome
    {
        try {
            $note = Notes::where('access_token_id', $accessTokenId)->findOrFail($id);

            $note->update($data);

            return Outcome::success(message: 'Alterações salvas com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar a nota: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível salvar as alterações.');
        }
    }
}
