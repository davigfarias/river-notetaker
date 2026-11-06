<?php

declare(strict_types=1);

namespace App\Ai\Prompts;

use App\DTO\PreparedExercise;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseType;

/**
 * Mensagem de avaliação, uma por tipo de exercício. Dados vão entre tags e nunca viram instrução.
 */
final class EvaluationPrompt
{
    public const VERSION = 'avaliar-v2';

    public static function build(PreparedExercise $exercise, StudyPrinciple $principle, string $userAnswer, ?string $chosenTitle = null): string
    {
        $title = PrincipleAiRules::safe($principle->title);
        $statement = PrincipleAiRules::safe($principle->statement);
        $answer = PrincipleAiRules::safe($userAnswer);
        $task = self::task($exercise, $chosenTitle);

        return <<<PROMPT
        {$task}

        <principio>
        Título: {$title}
        Enunciado: {$statement}
        </principio>

        <resposta_do_usuario>
        {$answer}
        </resposta_do_usuario>
        PROMPT;
    }

    private static function task(PreparedExercise $exercise, ?string $chosenTitle): string
    {
        $case = PrincipleAiRules::safe($exercise->question);
        $key = PrincipleAiRules::safe($exercise->referenceKey);
        $instruction = PrincipleAiRules::safe($exercise->instruction);

        return match ($exercise->type) {
            ExerciseType::ExplainWhy => 'Avalie a resposta do aluno ao pedido "Por que isso é verdade? Explique com suas palavras."',
            ExerciseType::ApplyScenario => "Avalie como o aluno aplicou o princípio ao caso.\n<caso>\n{$case}\n</caso>\nPergunta feita: {$instruction}\n<chave_de_aplicacao>\n{$key}\n</chave_de_aplicacao>\nA chave é uma referência, não a única resposta possível: aceite aplicações diferentes que sigam o princípio.",
            ExerciseType::EvaluateInterpretation => "O aluno deve achar o problema nesta interpretação.\n<interpretacao>\n{$case}\n</interpretacao>\n<falha_conhecida>\n{$key}\n</falha_conhecida>\nAvalie se o aluno identificou a falha e o princípio violado, e se explicou por quê.",
            ExerciseType::DistinguishPrinciples => 'O aluno escolheu o princípio "'.PrincipleAiRules::safe((string) $chosenTitle)."\" para o caso abaixo e justificou. Avalie SÓ a justificativa.\n<caso>\n{$case}\n</caso>\n<justificativa_de_referencia>\n{$key}\n</justificativa_de_referencia>",
            ExerciseType::CreateExample => 'O aluno criou um exemplo ou ilustração original do princípio. Dê FEEDBACK (não nota): adequação ao princípio, o que o exemplo ilustra bem e o que ficou fraco. O campo suggested_rating pode ser qualquer um; será ignorado.',
            default => 'Avalie a resposta do aluno.',
        };
    }
}
