<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\GradeClozeBlanks;
use App\Actions\RecordQuestionAttempt;
use App\Actions\TokenizeAnswerText;
use App\Models\AccessToken;
use App\Models\Question;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class SubmitClozeAnswerOrchestrator
{
    public function __construct(
        private TokenizeAnswerText $tokenizeAnswerText,
        private GradeClozeBlanks $gradeClozeBlanks,
        private RecordQuestionAttempt $recordQuestionAttempt,
    ) {}

    /**
     * Grade a cloze attempt against the question's fixed blank indices and
     * record it. The AttemptResultData shape isn't returned here because the
     * study page discards per-question feedback and only the results page
     * reads the attempt back.
     *
     * @param  array<int|string, string>  $given  keyed by word index
     */
    public function handle(Question $question, AccessToken $accessToken, array $given): Outcome
    {
        try {
            $blankIndices = $question->cloze_blank_indices ?? [];

            $tokensOutcome = $this->tokenizeAnswerText->handle($question->reference_answer);

            if (! $tokensOutcome->success) {
                return $tokensOutcome;
            }

            $gradeOutcome = $this->gradeClozeBlanks->handle($tokensOutcome->data, $blankIndices, $given);

            if (! $gradeOutcome->success) {
                return $gradeOutcome;
            }

            $blanks = $gradeOutcome->data['blanks'];
            $answerText = implode(', ', array_map(
                static fn (array $blank): string => $blank['given'],
                $blanks,
            ));

            $recordOutcome = $this->recordQuestionAttempt->handle(
                $question,
                $accessToken,
                $answerText,
                $gradeOutcome->data['score'],
                skipped: false,
                clozeBlanks: $blanks,
            );

            if (! $recordOutcome->success) {
                return $recordOutcome;
            }

            return Outcome::noViewMessage();
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível registrar sua resposta.');
        }
    }
}
