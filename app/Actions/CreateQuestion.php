<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\QuestionData;
use App\Models\Chapter;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class CreateQuestion
{
    public function handle(Chapter $chapter, QuestionData $data): Outcome
    {
        try {
            $question = $chapter->questions()->create([
                'prompt' => $data->prompt,
                'reference_answer' => $data->referenceAnswer,
                'keywords' => $data->keywords,
                'is_cloze' => $data->isCloze,
                'position' => $chapter->questions()->count(),
            ]);

            return Outcome::success(message: 'Pergunta criada.', data: $question);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível criar a pergunta.');
        }
    }
}
