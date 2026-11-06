<?php

declare(strict_types=1);

namespace App\Enums;

enum ExerciseType: string
{
    case RecallTitleToStatement = 'recall_title_to_statement';
    case RecallStatementToTitle = 'recall_statement_to_title';
    case ExplainWhy = 'explain_why';
    case ApplyScenario = 'apply_scenario';
    case DistinguishPrinciples = 'distinguish_principles';
    case EvaluateInterpretation = 'evaluate_interpretation';
    case CreateExample = 'create_example';

    public function bloomLevel(): BloomLevel
    {
        return match ($this) {
            self::RecallTitleToStatement, self::RecallStatementToTitle => BloomLevel::Lembrar,
            self::ExplainWhy => BloomLevel::Entender,
            self::ApplyScenario => BloomLevel::Aplicar,
            self::DistinguishPrinciples => BloomLevel::Analisar,
            self::EvaluateInterpretation => BloomLevel::Avaliar,
            self::CreateExample => BloomLevel::Criar,
        };
    }

    /** O enunciado precisa ser gerado pela IA (e fica em cache). */
    public function requiresAiGeneration(): bool
    {
        return match ($this) {
            self::ApplyScenario, self::DistinguishPrinciples, self::EvaluateInterpretation => true,
            default => false,
        };
    }

    /** A resposta do usuário é avaliada pela IA (ou recebe feedback dela). */
    public function requiresAiEvaluation(): bool
    {
        return match ($this) {
            self::RecallTitleToStatement, self::RecallStatementToTitle => false,
            default => true,
        };
    }
}
