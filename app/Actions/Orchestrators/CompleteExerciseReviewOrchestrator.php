<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\ApplyPrincipleReviewRating;
use App\Actions\FindOpenPrincipleSession;
use App\Actions\FinishPrincipleSession;
use App\Enums\SelfRating;
use App\Models\PrincipleAttempt;
use App\Support\Outcome;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class CompleteExerciseReviewOrchestrator
{
    public function __construct(
        private FindOpenPrincipleSession $findSession,
        private ApplyPrincipleReviewRating $applyRating,
        private FinishPrincipleSession $finish,
    ) {}

    /**
     * Aplica a nota do usuário. "Errei" reinsere o item no fim da sessão, uma única vez.
     * Devolve em `data` se a sessão acabou (`finished`).
     */
    public function handle(int $accessTokenId, int $attemptId, SelfRating $rating, ?CarbonImmutable $now = null): Outcome
    {
        try {
            $now ??= CarbonImmutable::now();

            $attempt = PrincipleAttempt::query()
                ->where('access_token_id', $accessTokenId)
                ->whereNull('self_rating')
                ->find($attemptId);
            $session = $this->findSession->handle($accessTokenId);

            if ($attempt === null || $session === null || $attempt->principle_session_id !== $session->id) {
                return Outcome::failure(message: 'Esta resposta já foi avaliada ou a sessão mudou. Recarregue a página.');
            }

            $finished = DB::transaction(function () use ($attempt, $session, $rating, $accessTokenId, $now): bool {
                $attempt->update(['self_rating' => $rating]);
                $this->applyRating->handle($accessTokenId, $attempt->principle_id, $rating, $now);

                $composition = $session->composition;
                $alreadyRetried = collect($composition)->contains(fn (array $i): bool => $i['principle_id'] === $attempt->principle_id && ($i['retry'] ?? false));

                if ($rating === SelfRating::Again && ! $alreadyRetried) {
                    $composition[] = ['principle_id' => $attempt->principle_id, 'type' => $attempt->type->value, 'retry' => true];
                    $session->update(['composition' => $composition]);
                }

                $done = $session->answered_count + 1 >= count($composition);

                if ($done) {
                    $this->finish->handle($accessTokenId, $session->id, $now);
                }

                return $done;
            });

            return Outcome::noViewMessage(data: ['finished' => $finished]);
        } catch (\Exception $e) {
            Log::error("Erro ao aplicar nota de princípio: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível salvar sua nota. Tente de novo.');
        }
    }
}
