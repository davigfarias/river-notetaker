<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Chapter;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class FindQuestionsForChapter
{
    public function handle(Chapter $chapter): Outcome
    {
        try {
            return Outcome::noViewMessage(data: $chapter->questions);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível carregar as perguntas do capítulo.');
        }
    }
}
