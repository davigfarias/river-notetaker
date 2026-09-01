<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessToken;
use App\Models\Chapter;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class FindLatestAttemptsForChapter
{
    public function handle(Chapter $chapter, AccessToken $accessToken): Outcome
    {
        try {
            $latestByQuestion = QuestionAttempt::query()
                ->forQuestions($chapter->questions()->pluck('id'))
                ->forAccessToken($accessToken)
                ->with('question')
                ->latest('id')
                ->get()
                ->unique('question_id')
                ->keyBy('question_id');

            $attempts = $chapter->questions
                ->map(fn (Question $question): ?QuestionAttempt => $latestByQuestion->get($question->id))
                ->filter()
                ->values();

            return Outcome::noViewMessage(data: $attempts);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível carregar as tentativas do capítulo.');
        }
    }
}
