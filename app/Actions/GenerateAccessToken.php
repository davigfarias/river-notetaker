<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\AccessTokenDTO;
use App\Models\AccessToken;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GenerateAccessToken
{
    public function handle(string $name): Outcome
    {
        try {
            $plainText = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $token = AccessToken::create([
                'name' => $name,
                'token' => hash('sha256', $plainText),
            ]);

            $result = ['plainTextToken' => $plainText, 'token' => AccessTokenDTO::fromModel($token)];

            return Outcome::success(message: 'Token gerado com sucesso.', data: $result);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível gerar o token.');
        }
    }
}
