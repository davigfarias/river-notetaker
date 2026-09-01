<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\BuildAnswerDiffSegments;
use App\Actions\BuildClozeResultSegments;
use App\Actions\FindLatestAttemptsForChapter;
use App\Actions\NormalizeAnswerText;
use App\DTO\ChapterResultsData;
use App\DTO\QuestionResultRow;
use App\Models\AccessToken;
use App\Models\Chapter;
use App\Models\QuestionAttempt;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class BuildChapterResultsOrchestrator
{
    public function __construct(
        private FindLatestAttemptsForChapter $findLatestAttemptsForChapter,
        private NormalizeAnswerText $normalizeAnswerText,
        private BuildAnswerDiffSegments $buildAnswerDiffSegments,
        private BuildClozeResultSegments $buildClozeResultSegments,
    ) {}

    public function handle(Chapter $chapter, AccessToken $accessToken): Outcome
    {
        try {
            $attemptsOutcome = $this->findLatestAttemptsForChapter->handle($chapter, $accessToken);

            if (! $attemptsOutcome->success) {
                return $attemptsOutcome;
            }

            $rows = [];

            foreach ($attemptsOutcome->data->values() as $index => $attempt) {
                /** @var QuestionAttempt $attempt */
                $answerSegments = [];
                $clozeSegments = null;

                if (! $attempt->skipped && $attempt->cloze_blanks !== null) {
                    $clozeOutcome = $this->buildClozeResultSegments->handle(
                        $attempt->question->reference_answer,
                        $attempt->cloze_blanks,
                    );

                    if (! $clozeOutcome->success) {
                        return $clozeOutcome;
                    }

                    $clozeSegments = $clozeOutcome->data;
                } elseif (! $attempt->skipped) {
                    $referenceOutcome = $this->normalizeAnswerText->handle($attempt->question->reference_answer);

                    if (! $referenceOutcome->success) {
                        return $referenceOutcome;
                    }

                    $answerOutcome = $this->normalizeAnswerText->handle($attempt->answer_text ?? '');

                    if (! $answerOutcome->success) {
                        return $answerOutcome;
                    }

                    $diffOutcome = $this->buildAnswerDiffSegments->handle($referenceOutcome->data, $answerOutcome->data);

                    if (! $diffOutcome->success) {
                        return $diffOutcome;
                    }

                    $answerSegments = $diffOutcome->data->answerSegments;
                }

                $rows[] = new QuestionResultRow(
                    position: $index + 1,
                    prompt: $attempt->question->prompt,
                    referenceAnswer: $attempt->question->reference_answer,
                    answerText: $attempt->answer_text,
                    score: $attempt->score,
                    skipped: $attempt->skipped,
                    answerSegments: $answerSegments,
                    clozeSegments: $clozeSegments,
                );
            }

            $scores = collect($rows)->pluck('score')->filter(fn (?int $score): bool => $score !== null);

            return Outcome::noViewMessage(data: new ChapterResultsData(
                chapterTitle: $chapter->title,
                averageScore: $scores->isEmpty() ? 0 : (int) round($scores->avg()),
                questionCount: count($rows),
                rows: $rows,
            ));
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível montar os resultados do capítulo.');
        }
    }
}
