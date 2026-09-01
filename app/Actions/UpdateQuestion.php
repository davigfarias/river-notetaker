<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\QuestionData;
use App\Models\Question;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdateQuestion
{
    public function handle(Question $question, QuestionData $data): Outcome
    {
        try {
            $question->update([
                'prompt' => $data->prompt,
                'reference_answer' => $data->referenceAnswer,
                'keywords' => $data->keywords,
                'is_cloze' => $data->isCloze,
            ]);

            return Outcome::success(message: 'Pergunta atualizada.', data: $question);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível atualizar a pergunta.');
        }
    }
}
