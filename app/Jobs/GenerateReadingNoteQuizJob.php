<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\PersistReadingNoteQuizQuestions;
use App\Actions\ValidateGeneratedQuestions;
use App\Ai\Agents\QuizQuestionGenerator;
use App\Support\MapConcepts;
use App\Support\ReviewSubject;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateReadingNoteQuizJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout;

    public function __construct(public Model $note, public string $kind = ReviewSubject::NOTE)
    {
        $this->timeout = (int) config('quiz.job_timeout', 60);
    }

    public function handle(ValidateGeneratedQuestions $validate, PersistReadingNoteQuizQuestions $persist): void
    {
        $content = ReviewSubject::contentFor($this->note);

        if ($content === '') {
            return;
        }

        $response = (new QuizQuestionGenerator)->prompt(MapConcepts::strip($content));

        $validated = $validate->handle($response['questions']);

        if (! $validated->success) {
            Log::error("Perguntas inválidas para a anotação {$this->note->id}: {$validated->message}");

            return;
        }

        $persist->handle(
            readingNoteId: $this->note->id,
            contentHash: hash('sha256', $content),
            questions: $validated->data,
            kind: $this->kind,
        );
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("Erro ao gerar perguntas da anotação {$this->note->id}: {$exception?->getMessage()}");
    }
}
