<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\Conceptualizer;
use App\Ai\Agents\PlainConceptualizer;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

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

            if ($technical === 'fora do escopo' || $plain === 'fora do escopo') {
                return Outcome::failure(
                    message: 'Este conceito está fora do escopo da teologia/filosofia reformada.'
                );
            }

            if (!filled($technical) || !filled($plain)) {
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
}
