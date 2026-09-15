<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessToken;
use App\Support\Outcome;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

final readonly class SetAccessTokenCode
{
    /**
     * Troca o código de um token existente mantendo o id — as notas continuam
     * vinculadas a ele, ao contrário de gerar um token novo.
     */
    public function handle(int $id, string $code): Outcome
    {
        if (! preg_match('/^\d{4}$/', $code)) {
            return Outcome::failure(message: 'O código deve ter exatamente 4 dígitos.');
        }

        try {
            $updated = (bool) AccessToken::where('id', $id)
                ->whereNull('revoked_at')
                ->update(['token' => hash('sha256', $code)]);

            if (! $updated) {
                return Outcome::failure(message: 'Token não encontrado ou revogado.');
            }

            return Outcome::success(message: 'Código do token atualizado com sucesso.');
        } catch (UniqueConstraintViolationException) {
            return Outcome::failure(message: 'Esse código já está em uso por outro token.');
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar o código do token.');
        }
    }
}
