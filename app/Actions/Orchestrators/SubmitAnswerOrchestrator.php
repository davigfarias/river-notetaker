<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\BuildAnswerDiffSegments;
use App\Actions\ComputeAnswerSimilarity;
use App\Actions\NormalizeAnswerText;
use App\Actions\RecordQuestionAttempt;
use App\DTO\AttemptResultData;
use App\Models\AccessToken;
use App\Models\Question;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class SubmitAnswerOrchestrator
{
    public function __construct(
        private NormalizeAnswerText $normalizeAnswerText,
        private ComputeAnswerSimilarity $computeAnswerSimilarity,
        private BuildAnswerDiffSegments $buildAnswerDiffSegments,
        private RecordQuestionAttempt $recordQuestionAttempt,
    ) {}

    public function handle(Question $question, AccessToken $accessToken, string $answerText): Outcome
    {
        try {
            $referenceOutcome = $this->normalizeAnswerText->handle($question->reference_answer);

            if (! $referenceOutcome->success) {
                return $referenceOutcome;
            }

            $answerOutcome = $this->normalizeAnswerText->handle($answerText);

            if (! $answerOutcome->success) {
                return $answerOutcome;
            }

            $referenceWords = $referenceOutcome->data;
            $answerWords = $answerOutcome->data;

            $scoreOutcome = $this->computeAnswerSimilarity->handle($referenceWords, $answerWords);

            if (! $scoreOutcome->success) {
                return $scoreOutcome;
            }

            $diffOutcome = $this->buildAnswerDiffSegments->handle($referenceWords, $answerWords);

            if (! $diffOutcome->success) {
                return $diffOutcome;
            }

            $recordOutcome = $this->recordQuestionAttempt->handle($question, $accessToken, $answerText, $scoreOutcome->data, skipped: false);

            if (! $recordOutcome->success) {
                return $recordOutcome;
            }

            return Outcome::noViewMessage(data: new AttemptResultData(
                score: $scoreOutcome->data,
                referenceAnswer: $question->reference_answer,
                answerText: $answerText,
                referenceSegments: $diffOutcome->data->referenceSegments,
                answerSegments: $diffOutcome->data->answerSegments,
            ));
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível registrar sua resposta.');
        }
    }
}
