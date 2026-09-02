<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\GenerateNoteSummaryJob;
use App\Models\Notes;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GenerateNoteSummary
{
    public function handle(int $noteId): Outcome
    {
        try {
            $note = Notes::findOrFail($noteId);

            GenerateNoteSummaryJob::dispatch($note);

            return Outcome::success(message: 'Resumo sendo gerado. Aguarde alguns segundos.');
        } catch (\Exception $e) {
            Log::error("Erro ao despachar a geração do resumo: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível iniciar a geração do resumo.');
        }
    }
}
