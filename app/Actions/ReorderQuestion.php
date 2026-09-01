<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Chapter;
use App\Models\Question;
use App\Support\Outcome;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class ReorderQuestion
{
    public function handle(Chapter $chapter, Question $question, int $position): Outcome
    {
        try {
            DB::transaction(function () use ($chapter, $question, $position) {
                $siblings = $chapter->questions()->where('id', '!=', $question->id)->get();
                $siblings->splice($position, 0, [$question]);

                foreach ($siblings->values() as $index => $sibling) {
                    if ($sibling->position !== $index) {
                        $sibling->update(['position' => $index]);
                    }
                }
            });

            return Outcome::noViewMessage();
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível reordenar as perguntas.');
        }
    }
}
