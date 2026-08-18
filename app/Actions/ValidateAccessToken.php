<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\AccessTokenDTO;
use App\Models\AccessToken;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class ValidateAccessToken
{
    public function handle(string $plainText): Outcome
    {
        try {
            $tokenModel = AccessToken::where('token', hash('sha256', $plainText))
                ->whereNull('revoked_at')
                ->first();

            $token = $tokenModel ? AccessTokenDTO::fromModel($tokenModel) : null;

            if (! $token) {
                return Outcome::failure(message: 'Código inválido.');
            }

            AccessToken::where('id', $token->id)->update(['last_used_at' => now()]);

            return Outcome::success(data: $token);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível validar o token.');
        }
    }
}
