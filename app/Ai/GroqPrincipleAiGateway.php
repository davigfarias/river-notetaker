<?php

declare(strict_types=1);

namespace App\Ai;

use App\Ai\Agents\PrincipleAnswerEvaluator;
use App\Ai\Prompts\EvaluationPrompt;
use App\DTO\AnswerEvaluation;
use App\DTO\StudyPrinciple;
use App\Exceptions\AiUnavailable;
use App\Exceptions\InvalidAiEvaluation;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;

/** Usa o provedor padrão do Laravel AI SDK (Groq, ver config/ai.php). */
final readonly class GroqPrincipleAiGateway implements PrincipleAiGateway
{
    public function evaluateAnswer(StudyPrinciple $principle, string $userAnswer): AnswerEvaluation
    {
        $startedAt = microtime(true);

        try {
            $response = (new PrincipleAnswerEvaluator)->prompt(
                EvaluationPrompt::build($principle, $userAnswer),
                timeout: (int) config('principios.ai.timeout_seconds'),
            );
        } catch (\Throwable $e) {
            Log::warning('Falha da IA ao avaliar princípio', ['principle_id' => $principle->id, 'erro' => $e::class]);

            throw new AiUnavailable($e->getMessage(), previous: $e);
        }

        if (! $response instanceof StructuredAgentResponse) {
            throw new InvalidAiEvaluation('A IA não devolveu resposta estruturada.');
        }

        $evaluation = AnswerEvaluation::fromArray($response->toArray());

        Log::info('Avaliação de princípio pela IA', [
            'principle_id' => $principle->id,
            'prompt_version' => EvaluationPrompt::VERSION,
            'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'verdict' => $evaluation->verdict->value,
            'confidence' => $evaluation->confidence,
        ]);

        return $evaluation;
    }
}
