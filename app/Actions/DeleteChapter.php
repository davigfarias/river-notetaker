<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Chapter;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class DeleteChapter
{
    public function handle(Chapter $chapter): Outcome
    {
        try {
            $chapter->delete();

            return Outcome::success(message: 'Capítulo excluído.');
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível excluir o capítulo.');
        }
    }
}
