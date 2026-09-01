<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ChapterData;
use App\Models\Chapter;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdateChapter
{
    public function handle(Chapter $chapter, ChapterData $data): Outcome
    {
        try {
            $chapter->update([
                'title' => $data->title,
            ]);

            return Outcome::success(message: 'Capítulo atualizado.', data: $chapter);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível atualizar o capítulo.');
        }
    }
}
