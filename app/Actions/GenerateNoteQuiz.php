<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\GenerateNoteQuizJob;
use App\Models\Notes;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GenerateNoteQuiz
{
    public function handle(int $noteId): Outcome
    {
        try {
            $note = Notes::findOrFail($noteId);

            GenerateNoteQuizJob::dispatch($note);

            return Outcome::success(message: 'Perguntas sendo geradas. Aguarde alguns segundos.');
        } catch (\Exception $e) {
            Log::error("Erro ao despachar a geração das perguntas: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível iniciar a geração das perguntas.');
        }
    }
}
