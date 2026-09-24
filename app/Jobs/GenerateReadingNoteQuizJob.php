<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\PersistReadingNoteQuizQuestions;
use App\Actions\ValidateGeneratedQuestions;
use App\Ai\Agents\QuizQuestionGenerator;
use App\Models\ReadingNote;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateReadingNoteQuizJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout;

    public function __construct(public ReadingNote $note)
    {
        $this->timeout = (int) config('quiz.job_timeout', 60);
    }

    public function handle(ValidateGeneratedQuestions $validate, PersistReadingNoteQuizQuestions $persist): void
    {
        $content = trim(($this->note->title !== null ? "{$this->note->title}\n\n" : '').$this->note->body);

        if ($content === '') {
            return;
        }

        $response = (new QuizQuestionGenerator)->prompt($content);

        $validated = $validate->handle($response['questions']);

        if (! $validated->success) {
            Log::error("Perguntas inválidas para a anotação {$this->note->id}: {$validated->message}");

            return;
        }

        $persist->handle(
            readingNoteId: $this->note->id,
            contentHash: hash('sha256', $content),
            questions: $validated->data,
        );
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("Erro ao gerar perguntas da anotação {$this->note->id}: {$exception?->getMessage()}");
    }
}
