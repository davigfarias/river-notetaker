<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PrincipleAttempt;
use App\Support\Outcome;

/** "Avaliar de novo": repete a avaliação de uma resposta ainda sem nota, só do dono. */
final readonly class RetryAttemptEvaluation
{
    public function __construct(
        private LoadStudyPrinciple $load,
        private EvaluateAttemptAnswer $evaluate,
    ) {}

    public function handle(int $accessTokenId, int $attemptId): Outcome
    {
        $attempt = PrincipleAttempt::query()
            ->where('access_token_id', $accessTokenId)
            ->whereNull('self_rating')
            ->whereNotNull('user_answer')
            ->find($attemptId);

        if ($attempt === null) {
            return Outcome::failure('Não encontrei essa resposta. Recarregue a página.');
        }

        return $this->evaluate->handle($attempt, $this->load->handle($attempt->principle_id));
    }
}
