<?php

namespace App\Ai\Agents;

use App\Ai\Agents\Concerns\DefinesReformedScope;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class Conceptualizer implements Agent
{
    use DefinesReformedScope;
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $scope = $this->reformedScopeInstructions();

        return <<<PROMPT
        Você define termos de filosofia e teologia. Dado um termo, forneça UMA definição em registro técnico e confessional, restrita ao sentido que o termo tem nessas áreas. Pode usar a terminologia técnica própria da tradição.

        {$scope}

        Escreva a definição de forma autônoma e completa em si mesma: não referencie, não continue e não pressuponha nenhuma outra definição.

        Responda apenas com o texto da definição, sem título nem rótulo.
        PROMPT;
    }
}
