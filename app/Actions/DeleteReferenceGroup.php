<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReferenceGroup;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class DeleteReferenceGroup
{
    /**
     * Removes the group only: its works stay in the library.
     */
    public function handle(int $groupId, int $accessTokenId): Outcome
    {
        try {
            ReferenceGroup::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($groupId)
                ->delete();

            return Outcome::success(message: 'Grupo apagado. As obras continuam na biblioteca.');
        } catch (\Exception $e) {
            Log::error("Erro ao apagar grupo de referências: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível apagar o grupo.');
        }
    }
}
