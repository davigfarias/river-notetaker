<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\PrincipleAiGateway;
use App\DTO\AnswerEvaluation;
use App\DTO\StudyPrinciple;
use App\Exceptions\AiUnavailable;
use App\Exceptions\InvalidAiEvaluation;
use App\Models\PrincipleAttempt;
use App\Support\Outcome;
use Carbon\CarbonImmutable;

/**
 * Pede à IA a avaliação de uma tentativa e grava o resultado nela. Nunca lança: quando a IA
 * não puder ajudar, devolve falha com um aviso em português simples e a sessão segue.
 * Resposta inválida ganha novas tentativas (principios.ai.max_retries).
 */
final readonly class EvaluateAttemptAnswer
{
    private const OFFLINE_NOTICE = 'Sem IA agora; compare com o gabarito e escolha a nota que for honesta.';

    public function __construct(
        private PrincipleAiGateway $gateway,
        private ReservePrincipleAiCall $reserve,
    ) {}

    public function handle(PrincipleAttempt $attempt, StudyPrinciple $principle, ?CarbonImmutable $now = null): Outcome
    {
        $now ??= CarbonImmutable::now();

        if (! config('principios.ai.enabled')) {
            return Outcome::failure(self::OFFLINE_NOTICE);
        }

        if (! $this->reserve->handle($attempt->access_token_id, $now)) {
            return Outcome::failure('Você já usou as avaliações com IA de hoje. Compare com o gabarito e escolha sua nota.');
        }

        try {
            $evaluation = $this->evaluate($principle, (string) $attempt->user_answer, (int) config('principios.ai.max_retries'));
        } catch (AiUnavailable|InvalidAiEvaluation) {
            return Outcome::failure('A IA não respondeu direito agora. Sua resposta está salva: compare com o gabarito ou tente avaliar de novo.');
        }

        $attempt->update([
            'ai_evaluation' => $evaluation->toArray(),
            'ai_verdict' => $evaluation->verdict,
            'ai_suggested_rating' => $evaluation->suggestedRating,
        ]);

        return Outcome::noViewMessage(data: $evaluation);
    }

    /**
     * @throws AiUnavailable
     * @throws InvalidAiEvaluation
     */
    private function evaluate(StudyPrinciple $principle, string $answer, int $retriesLeft): AnswerEvaluation
    {
        try {
            return $this->gateway->evaluateAnswer($principle, $answer);
        } catch (InvalidAiEvaluation $e) {
            return $retriesLeft > 0 ? $this->evaluate($principle, $answer, $retriesLeft - 1) : throw $e;
        }
    }
}
