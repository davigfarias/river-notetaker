<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessToken;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class CheckAccessTokenStillValid
{
    public function handle(int $id): Outcome
    {
        try {
            $isActive = AccessToken::where('id', $id)
                ->whereNull('revoked_at')
                ->exists();

            if (! $isActive) {
                return Outcome::failure(message: 'Token inválido ou revogado.');
            }

            return Outcome::noViewMessage();
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível validar o token.');
        }
    }
}
