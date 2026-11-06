<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Prompts\PrincipleAiRules;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ScenarioGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    public const VERSION = 'cenario-v2';

    public function instructions(): Stringable|string
    {
        $authority = PrincipleAiRules::AUTHORITY;
        $data = PrincipleAiRules::DATA_NOT_INSTRUCTIONS;
        $tone = PrincipleAiRules::TONE;
        $tradition = PrincipleAiRules::TRADITION;
        $verses = PrincipleAiRules::NO_FAKE_VERSES;

        return <<<PROMPT
        Você cria casos curtos para um aluno de teologia praticar a APLICAÇÃO de um princípio. {$tone}

        {$tradition}

        REGRAS
        1. {$authority}
        2. {$data}
        3. Escreva um caso concreto de 3 a 6 frases em que o princípio se aplica, na vida de uma igreja
           presbiteriana: aconselhamento pastoral, reunião de conselho, escola dominical, estudo de um
           texto bíblico, culto ou uma objeção vinda de outra tradição. Varie o contexto.
        4. {$verses}
        5. "question": a pergunta ao aluno (por padrão "Como este princípio se aplica aqui?").
        6. "expected_application": a aplicação esperada. NUNCA a revele dentro do caso.
        7. "key_points": 2 a 4 pontos que uma boa resposta cobre.
        8. Em <cenarios_anteriores> estão casos já usados: não repita situação nem personagens.
        9. Responda APENAS no formato estruturado pedido.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scenario' => $schema->string()->required(),
            'question' => $schema->string()->required(),
            'expected_application' => $schema->string()->required(),
            'key_points' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
