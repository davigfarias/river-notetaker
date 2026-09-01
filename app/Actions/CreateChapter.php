<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ChapterData;
use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class CreateChapter
{
    public function handle(ReferenceMaterial $referenceMaterial, ChapterData $data): Outcome
    {
        try {
            $chapter = $referenceMaterial->chapters()->create([
                'title' => $data->title,
                'position' => $referenceMaterial->chapters()->count(),
            ]);

            return Outcome::success(message: 'Capítulo criado.', data: $chapter);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível criar o capítulo.');
        }
    }
}
