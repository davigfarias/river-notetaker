<?php

declare(strict_types=1);

namespace App\Ai;

use App\DTO\AnswerEvaluation;
use App\DTO\DistinctionPayload;
use App\DTO\FlawedInterpretationPayload;
use App\DTO\PreparedExercise;
use App\DTO\ScenarioPayload;
use App\DTO\StudyPrinciple;
use App\Exceptions\AiUnavailable;
use App\Exceptions\InvalidAiEvaluation;
use App\Exceptions\InvalidExercisePayload;

/**
 * Única porta de entrada da IA para a sessão de princípios: as Actions não chamam o SDK.
 * Todas as respostas voltam validadas (DTOs). Falha de rede é AiUnavailable; formato errado é
 * InvalidAiEvaluation / InvalidExercisePayload.
 */
interface PrincipleAiGateway
{
    /**
     * @throws AiUnavailable
     * @throws InvalidAiEvaluation
     */
    public function evaluateAnswer(PreparedExercise $exercise, StudyPrinciple $principle, string $userAnswer, ?string $chosenTitle = null): AnswerEvaluation;

    /**
     * @param  array<int, string>  $previousScenarios  casos já usados, para a IA não repetir
     *
     * @throws AiUnavailable
     * @throws InvalidExercisePayload
     */
    public function generateScenario(StudyPrinciple $principle, array $previousScenarios): ScenarioPayload;

    /**
     * @param  array<int, StudyPrinciple>  $neighbors
     *
     * @throws AiUnavailable
     * @throws InvalidExercisePayload
     */
    public function generateDistinction(StudyPrinciple $target, array $neighbors): DistinctionPayload;

    /**
     * @throws AiUnavailable
     * @throws InvalidExercisePayload
     */
    public function generateFlawedInterpretation(StudyPrinciple $principle): FlawedInterpretationPayload;
}
