<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class PlainConceptualizer implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        Dado um conceito, forneça UMA definição em linguagem simples e acessível, como se explicasse para alguém leigo, sem jargão técnico. O significado deve permanecer restrito à filosofia ou teologia reformada (tradição confessional, ex.: Confissão de Fé de Westminster e seus comentaristas).

        Escreva a definição de forma autônoma e completa em si mesma: não referencie, não continue e não pressuponha nenhuma outra definição.

        Responda apenas com o texto da definição, sem título nem rótulo.

        Se o conceito não pertencer à filosofia ou teologia reformada, ou não puder ser explicado sem sair dessas áreas, responda apenas "fora do escopo".
        PROMPT;
    }
}
