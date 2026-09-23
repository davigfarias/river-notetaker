<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\RecordNoteReview;
use App\Models\QuizQuestion;
use App\Support\Outcome;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Log;

/**
 * Corrige as respostas de múltipla escolha da revisão e deixa o placar
 * decidir: acertou a maioria, a nota sobe um degrau da escada; ficou abaixo
 * do piso, ela volta para o primeiro.
 */
final readonly class SubmitNoteQuizOrchestrator
{
    public function __construct(
        private RecordNoteReview $recordNoteReview,
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
    public function handle(int $noteId, int $accessTokenId, array $quizQuestionIds, array $answers): Outcome
    {
        try {
            $questions = QuizQuestion::query()
                ->where('note_id', $noteId)
                ->whereIn('id', $quizQuestionIds)
                ->get();

            if ($questions->isEmpty()) {
                return Outcome::failure(message: 'Não há perguntas para conferir.');
            }

            $results = $questions->map(function (QuizQuestion $question) use ($answers): array {
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

            $reviewOutcome = $this->recordNoteReview->handle($noteId, $accessTokenId, $recalled);

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
