<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Outcome;
use App\Support\ReviewSubject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final readonly class PersistReadingNoteQuizQuestions
{
    /**
     * @param  array<int, array{question: string, correct_answer: string, distractors: array<int, string>}>  $questions
     * @return Outcome data: Collection<int, Model>
     */
    public function handle(int $readingNoteId, string $contentHash, array $questions, string $provider = 'groq', ?string $model = null, string $kind = ReviewSubject::NOTE): Outcome
    {
        try {
            $created = collect($questions)->map(fn (array $item): Model => ReviewSubject::quizClass($kind)::create([
                ReviewSubject::quizKey($kind) => $readingNoteId,
                'question' => $item['question'],
                'correct_answer' => $item['correct_answer'],
                'distractors' => $item['distractors'],
                'content_hash' => $contentHash,
                'provider' => $provider,
                'model' => $model,
            ]));

            return Outcome::noViewMessage(data: $created);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível salvar as perguntas geradas.');
        }
    }
}
