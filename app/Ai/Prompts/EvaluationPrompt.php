<?php

declare(strict_types=1);

namespace App\Ai\Prompts;

use App\DTO\StudyPrinciple;

/**
 * Mensagem de avaliação. Dados vão entre tags e nunca viram instrução: `<` e `>` do texto do
 * usuário são trocados para ele não conseguir fechar a tag e escrever fora dela.
 */
final class EvaluationPrompt
{
    public const VERSION = 'avaliar-v1';

    public static function build(StudyPrinciple $principle, string $userAnswer): string
    {
        $title = self::safe($principle->title);
        $statement = self::safe($principle->statement);
        $answer = self::safe($userAnswer);

        return <<<PROMPT
        Avalie a resposta do aluno ao pedido "Por que isso é verdade? Explique com suas palavras."

        <principio>
        Título: {$title}
        Enunciado: {$statement}
        </principio>

        <resposta_do_usuario>
        {$answer}
        </resposta_do_usuario>
        PROMPT;
    }

    private static function safe(string $text): string
    {
        return str_replace(['<', '>'], ['‹', '›'], $text);
    }
}
