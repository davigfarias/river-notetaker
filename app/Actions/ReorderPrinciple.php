<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Principle;
use App\Models\PrincipleTopic;
use App\Support\Outcome;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class ReorderPrinciple
{
    /**
     * Move o princípio para a posição dada dentro da categoria (null = sem categoria).
     */
    public function handle(PrincipleTopic $topic, Principle $principle, int $position, ?int $categoryId = null): Outcome
    {
        try {
            DB::transaction(function () use ($topic, $principle, $position, $categoryId) {
                $siblings = $topic->principles()
                    ->where('principle_category_id', $categoryId)
                    ->where('id', '!=', $principle->id)
                    ->get();
                $siblings->splice($position, 0, [$principle]);

                foreach ($siblings->values() as $index => $sibling) {
                    $sibling->principle_category_id = $categoryId;
                    $sibling->position = $index;
                    $sibling->save();
                }
            });

            return Outcome::noViewMessage();
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível reordenar os princípios.');
        }
    }
}
