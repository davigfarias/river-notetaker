<?php

declare(strict_types=1);

namespace App\Ai;

use App\DTO\AnswerEvaluation;
use App\DTO\StudyPrinciple;
use App\Exceptions\AiUnavailable;
use App\Exceptions\InvalidAiEvaluation;

/**
 * Única porta de entrada da IA para a sessão de princípios: as Actions não chamam o SDK.
 */
interface PrincipleAiGateway
{
    /**
     * @throws AiUnavailable
     * @throws InvalidAiEvaluation
     */
    public function evaluateAnswer(StudyPrinciple $principle, string $userAnswer): AnswerEvaluation;
}
