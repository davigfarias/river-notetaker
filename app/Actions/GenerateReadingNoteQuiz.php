<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\GenerateReadingNoteQuizJob;
use App\Models\ReadingNote;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GenerateReadingNoteQuiz
{
    public function handle(int $readingNoteId): Outcome
    {
        try {
            $note = ReadingNote::findOrFail($readingNoteId);

            GenerateReadingNoteQuizJob::dispatch($note);

            return Outcome::success(message: 'Perguntas sendo geradas. Aguarde alguns segundos.');
        } catch (\Exception $e) {
            Log::error("Erro ao despachar a geração das perguntas: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível iniciar a geração das perguntas.');
        }
    }
}
