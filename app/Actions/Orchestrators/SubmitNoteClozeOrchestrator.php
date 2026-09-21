<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\GradeClozeBlanks;
use App\Actions\RecordNoteReview;
use App\Actions\TokenizeAnswerText;
use App\Support\Outcome;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Log;

/**
 * Corrige as lacunas do resumo escrito pelo aluno e deixa o placar decidir a
 * revisão: acertou a maioria, a nota sobe um degrau da escada; ficou abaixo do
 * piso, ela volta para o primeiro. O julgamento deixa de ser autoavaliação.
 */
final readonly class SubmitNoteClozeOrchestrator
{
    public function __construct(
        private TokenizeAnswerText $tokenizeAnswerText,
        private GradeClozeBlanks $gradeClozeBlanks,
        private RecordNoteReview $recordNoteReview,
        #[Config('cloze.pass_score')] private int $passScore = 70,
    ) {}

    /**
     * @param  array<int, int>  $blankIndices
     * @param  array<int|string, string>  $given  keyed by word index
     * @return Outcome data: array{
     *                 score: int,
     *                 recalled: bool,
     *                 blanks: array<int, array{index: int, expected: string, given: string, correct: bool}>
     *                 }
     */
    public function handle(
        int $noteId,
        int $accessTokenId,
        string $summary,
        array $blankIndices,
        array $given,
    ): Outcome {
        try {
            $tokensOutcome = $this->tokenizeAnswerText->handle($summary);

            if (! $tokensOutcome->success) {
                return $tokensOutcome;
            }

            $gradeOutcome = $this->gradeClozeBlanks->handle($tokensOutcome->data, $blankIndices, $given);

            if (! $gradeOutcome->success) {
                return $gradeOutcome;
            }

            $score = (int) $gradeOutcome->data['score'];
            $recalled = $score >= $this->passScore;

            $reviewOutcome = $this->recordNoteReview->handle($noteId, $accessTokenId, $recalled);

            if (! $reviewOutcome->success) {
                return $reviewOutcome;
            }

            return Outcome::noViewMessage(data: [
                'score' => $score,
                'recalled' => $recalled,
                'blanks' => $gradeOutcome->data['blanks'],
            ]);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível corrigir a sua revisão.');
        }
    }
}
