<?php

declare(strict_types=1);

namespace App\Ai;

use App\Ai\Agents\DistinctionGenerator;
use App\Ai\Agents\FlawedInterpretationGenerator;
use App\Ai\Agents\ScenarioGenerator;
use App\Enums\ExerciseType;

/**
 * Versão atual do prompt de cada tipo gerado. O cache só serve exercícios desta versão:
 * mudar o prompt (ex.: o contexto confessional) descarta o estoque gerado com o prompt antigo.
 */
final class PromptVersions
{
    public static function for(ExerciseType $type): string
    {
        return match ($type) {
            ExerciseType::ApplyScenario => ScenarioGenerator::VERSION,
            ExerciseType::DistinguishPrinciples => DistinctionGenerator::VERSION,
            ExerciseType::EvaluateInterpretation => FlawedInterpretationGenerator::VERSION,
            default => throw new \InvalidArgumentException("Tipo {$type->value} não é gerado."),
        };
    }
}
