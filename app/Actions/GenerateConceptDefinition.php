<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\Conceptualizer;
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

            $response = (new Conceptualizer)->prompt($term)->text;

            if (trim($response) === 'fora do escopo') {
                return Outcome::failure(
                    message: 'Este conceito está fora do escopo da teologia/filosofia reformada.'
                );
            }

            $definitions = $this->parse($response);

            if ($definitions === null) {
                return Outcome::failure(
                    message: 'Não foi possível gerar definições para este termo.'
                );
            }

            return Outcome::success(
                message: 'Definições geradas com sucesso.',
                data: $definitions,
            );
        } catch (\Exception $e) {
            Log::error("Erro ao gerar definição do conceito: {$e->getMessage()}");

            return Outcome::failure(
                message: 'Ocorreu um erro ao gerar as definições. Tente novamente.'
            );
        }
    }

    /**
     * @return array{definition_a: string, definition_b: string}|null
     */
    private function parse(string $response): ?array
    {
        $parts = preg_split('/---DEFINICAO_[AB]---/', $response);

        $parts = array_map('trim', $parts);

        $parts = array_values(array_filter($parts));

        if (count($parts) < 2) {
            return null;
        }

        return [
            'definition_a' => $parts[0],
            'definition_b' => $parts[1],
        ];
    }
}
