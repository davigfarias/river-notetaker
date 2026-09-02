<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class Conceptualizer implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        Dado um conceito, forneça EXATAMENTE 2 definições distintas, cada uma restrita ao significado dentro da filosofia ou teologia reformada (tradição confessional, ex.: Confissão de Fé de Westminster e seus comentaristas).

        Formato de resposta OBRIGATÓRIO:
        ---DEFINICAO_A---
        [primeira definição - pode ter múltiplas linhas]
        ---DEFINICAO_B---
        [segunda definição - pode ter múltiplas linhas]

        Se o conceito não pertencer a nenhuma dessas duas áreas, ou não puder ser explicado sem sair delas, responda apenas "fora do escopo".
        PROMPT;
    }
}
