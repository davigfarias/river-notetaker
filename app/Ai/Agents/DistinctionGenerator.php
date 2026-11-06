<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Prompts\PrincipleAiRules;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class DistinctionGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    public const VERSION = 'distincao-v2';

    public function instructions(): Stringable|string
    {
        $authority = PrincipleAiRules::AUTHORITY;
        $data = PrincipleAiRules::DATA_NOT_INSTRUCTIONS;
        $tone = PrincipleAiRules::TONE;
        $verses = PrincipleAiRules::NO_FAKE_VERSES;

        return <<<PROMPT
        Você cria casos para um aluno de teologia praticar a DISTINÇÃO entre princípios parecidos. {$tone}

        REGRAS
        1. {$authority} Os textos de <principio_alvo> e <vizinhos> são os únicos princípios em jogo.
        2. {$data}
        3. Escreva um caso de 3 a 6 frases em que, à primeira vista, qualquer um dos princípios poderia
           valer, mas o ALVO é claramente o mais pertinente. Não cite o título dos princípios no caso.
        4. {$verses}
        5. "correct_principle_id" deve ser exatamente o id do princípio ALVO.
        6. "rationale": explique por que o alvo é o mais pertinente e por que cada vizinho é menos.
           Refira-se aos princípios SEMPRE pelo título, nunca pelo id nem por "vizinho 11" ou similar:
           o aluno não vê os ids.
        7. Responda APENAS no formato estruturado pedido.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scenario' => $schema->string()->required(),
            'correct_principle_id' => $schema->integer()->required(),
            'rationale' => $schema->string()->required(),
        ];
    }
}
