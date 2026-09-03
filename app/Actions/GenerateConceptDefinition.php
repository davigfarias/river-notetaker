<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\Conceptualizer;
use App\Ai\Agents\PlainConceptualizer;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class GenerateConceptDefinition
{
    public function handle(string $term): Outcome
    {
        try {
            $term = trim($term);

            if (strlen($term) < 5) {
                return Outcome::failure(message: 'O termo deve ter pelo menos 5 caracteres.');
            }

            $technical = trim((new Conceptualizer)->prompt($term)->text);
            $plain = trim((new PlainConceptualizer)->prompt($term)->text);

            if ($this->isOutOfScope($technical) || $this->isOutOfScope($plain)) {
                return Outcome::failure(
                    message: 'Este termo está fora do escopo de filosofia, religião e teologia.'
                );
            }

            if (! filled($technical) || ! filled($plain)) {
                return Outcome::failure(
                    message: 'Não foi possível gerar definições para este termo.'
                );
            }

            return Outcome::success(
                message: 'Definições geradas com sucesso.',
                data: [
                    'definition_a' => $technical,
                    'definition_b' => $plain,
                ],
            );
        } catch (\Exception $e) {
            Log::error("Erro ao gerar definição do conceito: {$e->getMessage()}");

            return Outcome::failure(
                message: 'Ocorreu um erro ao gerar as definições. Tente novamente.'
            );
        }
    }

    /**
     * Reconhece a resposta "fora do escopo" ainda que venha com pontuação,
     * aspas ou capitalização diferentes.
     */
    private function isOutOfScope(string $text): bool
    {
        return Str::of($text)
            ->lower()
            ->trim(" \t\n\r\0\x0B\"'.")
            ->exactly('fora do escopo');
    }
}
