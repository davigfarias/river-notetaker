<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\FindQuestionsForChapter;
use App\DTO\StudySessionData;
use App\Models\Chapter;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class StartStudySessionOrchestrator
{
    public function __construct(private FindQuestionsForChapter $findQuestionsForChapter) {}

    public function handle(Chapter $chapter): Outcome
    {
        try {
            $questionsOutcome = $this->findQuestionsForChapter->handle($chapter);

            if (! $questionsOutcome->success) {
                return $questionsOutcome;
            }

            return Outcome::noViewMessage(data: new StudySessionData(
                referenceMaterialTitle: $chapter->referenceMaterial->title,
                chapterTitle: $chapter->title,
                questionIds: $questionsOutcome->data->pluck('id')->all(),
            ));
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível iniciar a sessão de estudo.');
        }
    }
}
