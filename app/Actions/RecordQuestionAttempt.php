<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessToken;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class RecordQuestionAttempt
{
    /**
     * @param  array<int, array{index: int, expected: string, given: string, correct: bool}>|null  $clozeBlanks
     */
    public function handle(Question $question, AccessToken $accessToken, ?string $answerText, ?int $score, bool $skipped, ?array $clozeBlanks = null): Outcome
    {
        try {
            $attempt = QuestionAttempt::create([
                'question_id' => $question->id,
                'access_token_id' => $accessToken->id,
                'answer_text' => $answerText,
                'score' => $score,
                'cloze_blanks' => $clozeBlanks,
                'skipped' => $skipped,
            ]);

            return Outcome::noViewMessage(data: $attempt);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível registrar a tentativa.');
        }
    }
}
