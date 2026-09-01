<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Question;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class RefreshClozeBlanks
{
    public function __construct(
        private TokenizeAnswerText $tokenizeAnswerText,
        private SelectClozeBlanks $selectClozeBlanks,
    ) {}

    /**
     * Keep questions.cloze_blank_indices in sync with the cloze flag. The
     * blanks are picked once and kept stable across study sessions; they are
     * only recomputed when cloze is first enabled or the reference answer
     * changes, and cleared when cloze is turned off.
     */
    public function handle(Question $question): Outcome
    {
        try {
            if (! $question->is_cloze) {
                // Eloquent skips the query when the value is already null.
                $question->update(['cloze_blank_indices' => null]);

                return Outcome::noViewMessage();
            }

            $answerUnchanged = $question->cloze_blank_indices !== null
                && ! $question->wasChanged('reference_answer');

            if ($answerUnchanged) {
                return Outcome::noViewMessage();
            }

            $tokensOutcome = $this->tokenizeAnswerText->handle($question->reference_answer);

            if (! $tokensOutcome->success) {
                return $tokensOutcome;
            }

            $blanksOutcome = $this->selectClozeBlanks->handle($tokensOutcome->data);

            if (! $blanksOutcome->success) {
                return $blanksOutcome;
            }

            $question->update(['cloze_blank_indices' => $blanksOutcome->data]);

            return Outcome::noViewMessage();
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível preparar as lacunas do Cloze.');
        }
    }
}
