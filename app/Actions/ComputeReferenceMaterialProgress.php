<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\BookProgress;
use App\Models\AccessToken;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

final readonly class ComputeReferenceMaterialProgress
{
    public function handle(ReferenceMaterial $referenceMaterial, AccessToken $accessToken): Outcome
    {
        try {
            $questionIds = Question::forReferenceMaterial($referenceMaterial)->pluck('id');

            $total = $questionIds->count();

            $attempted = QuestionAttempt::forQuestions($questionIds)
                ->forAccessToken($accessToken)
                ->distinct('question_id')
                ->count('question_id');

            $lastStudiedAt = QuestionAttempt::forQuestions($questionIds)
                ->forAccessToken($accessToken)
                ->max('created_at');

            return Outcome::noViewMessage(data: new BookProgress(
                totalQuestions: $total,
                attemptedQuestions: $attempted,
                percent: $total === 0 ? 0 : (int) round($attempted / $total * 100),
                lastStudiedAt: $lastStudiedAt ? Carbon::parse($lastStudiedAt) : null,
            ));
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível calcular o progresso da referência.');
        }
    }
}
