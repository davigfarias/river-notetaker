<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Prompts\PrincipleAiRules;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class FlawedInterpretationGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    public const VERSION = 'interpretacao-v2';

    public function instructions(): Stringable|string
    {
        $authority = PrincipleAiRules::AUTHORITY;
        $data = PrincipleAiRules::DATA_NOT_INSTRUCTIONS;
        $tone = PrincipleAiRules::TONE;
        $tradition = PrincipleAiRules::TRADITION;
        $verses = PrincipleAiRules::NO_FAKE_VERSES;

        return <<<PROMPT
        Você cria exercícios para um aluno de teologia praticar a AVALIAÇÃO de interpretações. {$tone}

        {$tradition}

        REGRAS
        1. {$authority}
        2. {$data}
        3. Escreva uma interpretação, argumento ou leitura de texto de 3 a 5 frases que contém UM erro
           sutil e plausível que viola o princípio de forma identificável. O erro deve ser do tipo
           comum fora da tradição reformada confessional (por exemplo, leitura arminiana, dispensacionalista
           ou moralista do texto), nunca uma doutrina inventada.
        4. Mantenha o erro curto e didático: não defenda heresias de modo persuasivo além do necessário
           para o exercício e não escreva nada ofensivo.
        5. {$verses}
        6. "flaw_explanation": explique qual é o erro e como ele viola o princípio. NUNCA o revele
           dentro da interpretação.
        7. Responda APENAS no formato estruturado pedido.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'interpretation' => $schema->string()->required(),
            'flaw_explanation' => $schema->string()->required(),
        ];
    }
}
