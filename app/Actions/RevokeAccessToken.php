<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessToken;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class RevokeAccessToken
{
    public function handle(int $id): Outcome
    {
        try {
            $revoked = (bool) AccessToken::where('id', $id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            if (! $revoked) {
                return Outcome::failure(message: 'Token não encontrado ou já revogado.');
            }

            return Outcome::success(message: 'Token revogado com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível revogar o token.');
        }
    }
}
