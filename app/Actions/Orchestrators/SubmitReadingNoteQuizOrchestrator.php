<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\RecordReadingNoteReview;
use App\Models\ReadingNoteQuizQuestion;
use App\Support\Outcome;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Log;

/**
 * Corrige as respostas de múltipla escolha da revisão de uma anotação de
 * leitura e deixa o placar decidir: acertou a maioria, ela sobe um degrau da
 * escada; ficou abaixo do piso, volta para o primeiro.
 */
final readonly class SubmitReadingNoteQuizOrchestrator
{
    public function __construct(
        private RecordReadingNoteReview $recordReadingNoteReview,
        #[Config('quiz.pass_score')] private int $passScore = 70,
    ) {}

    /**
     * @param  array<int, int>  $quizQuestionIds  as perguntas apresentadas nesta sessão
     * @param  array<int, string>  $answers  keyed by quiz_question_id
     * @return Outcome data: array{
     *                 score: int,
     *                 recalled: bool,
     *                 results: array<int, array{question: string, chosen: string, correct_answer: string, correct: bool}>
     *                 }
     */
    public function handle(int $readingNoteId, int $accessTokenId, array $quizQuestionIds, array $answers): Outcome
    {
        try {
            $questions = ReadingNoteQuizQuestion::query()
                ->where('reading_note_id', $readingNoteId)
                ->whereIn('id', $quizQuestionIds)
                ->get();

            if ($questions->isEmpty()) {
                return Outcome::failure(message: 'Não há perguntas para conferir.');
            }

            $results = $questions->map(function (ReadingNoteQuizQuestion $question) use ($answers): array {
                $chosen = (string) ($answers[$question->id] ?? '');

                return [
                    'question' => $question->question,
                    'chosen' => $chosen,
                    'correct_answer' => $question->correct_answer,
                    'correct' => $chosen !== '' && $chosen === $question->correct_answer,
                ];
            })->values();

            $score = (int) round(
                $results->filter(fn (array $result): bool => $result['correct'])->count() / $results->count() * 100
            );
            $recalled = $score >= $this->passScore;

            $reviewOutcome = $this->recordReadingNoteReview->handle($readingNoteId, $accessTokenId, $recalled);

            if (! $reviewOutcome->success) {
                return $reviewOutcome;
            }

            return Outcome::noViewMessage(data: [
                'score' => $score,
                'recalled' => $recalled,
                'results' => $results->all(),
            ]);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível corrigir a sua revisão.');
        }
    }
}
