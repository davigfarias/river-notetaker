<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReferenceGroup;
use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class ToggleReferenceGroupMaterial
{
    /**
     * Puts the work in the group, or takes it out when it is already there.
     */
    public function handle(int $groupId, int $materialId, int $accessTokenId): Outcome
    {
        try {
            $group = ReferenceGroup::query()->where('access_token_id', $accessTokenId)->findOrFail($groupId);
            $material = ReferenceMaterial::query()->where('access_token_id', $accessTokenId)->findOrFail($materialId);

            $group->materials()->toggle($material->id);

            return Outcome::noViewMessage();
        } catch (\Exception $e) {
            Log::error("Erro ao alterar obra no grupo de referências: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível alterar o grupo desta obra.');
        }
    }
}
