<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReferenceGroup;
use App\Support\Outcome;
use App\Support\TextNormalizer;
use Illuminate\Support\Facades\Log;

final readonly class CreateReferenceGroup
{
    public function handle(string $name, int $accessTokenId): Outcome
    {
        try {
            $name = trim($name);
            $folded = TextNormalizer::fold($name);

            $duplicate = ReferenceGroup::query()
                ->where('access_token_id', $accessTokenId)
                ->pluck('name')
                ->contains(fn (string $existing) => TextNormalizer::fold($existing) === $folded);

            if ($duplicate) {
                return Outcome::failure(message: "Você já tem um grupo chamado '{$name}'. Escolha outro nome.");
            }

            $group = ReferenceGroup::create(['access_token_id' => $accessTokenId, 'name' => $name]);

            return Outcome::success(message: "Grupo '{$group->name}' criado.", data: $group);
        } catch (\Exception $e) {
            Log::error("Erro ao criar grupo de referências: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível criar o grupo.');
        }
    }
}
