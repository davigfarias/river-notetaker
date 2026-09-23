<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\PersistQuizQuestions;
use App\Actions\ValidateGeneratedQuestions;
use App\Ai\Agents\QuizQuestionGenerator;
use App\Models\Notes;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateNoteQuizJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout;

    public function __construct(public Notes $note)
    {
        $this->timeout = (int) config('quiz.job_timeout', 60);
    }

    public function handle(ValidateGeneratedQuestions $validate, PersistQuizQuestions $persist): void
    {
        $summary = trim((string) $this->note->summary);

        if ($summary === '') {
            return;
        }

        $response = (new QuizQuestionGenerator)->prompt($summary);

        $validated = $validate->handle($response['questions']);

        if (! $validated->success) {
            Log::error("Perguntas inválidas para a nota {$this->note->id}: {$validated->message}");

            return;
        }

        $persist->handle(
            noteId: $this->note->id,
            contentHash: hash('sha256', $summary),
            questions: $validated->data,
        );
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("Erro ao gerar perguntas da nota {$this->note->id}: {$exception?->getMessage()}");
    }
}
