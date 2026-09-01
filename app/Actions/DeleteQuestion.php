<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Question;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class DeleteQuestion
{
    public function handle(Question $question): Outcome
    {
        try {
            $question->delete();

            return Outcome::success(message: 'Pergunta excluída.');
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível excluir a pergunta.');
        }
    }
}
