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
    public function handle(PrincipleTopic $topic, Principle $principle, int $position): Outcome
    {
        try {
            DB::transaction(function () use ($topic, $principle, $position) {
                $siblings = $topic->principles()->where('id', '!=', $principle->id)->get();
                $siblings->splice($position, 0, [$principle]);

                foreach ($siblings->values() as $index => $sibling) {
                    if ($sibling->position !== $index) {
                        $sibling->update(['position' => $index]);
                    }
                }
            });

            return Outcome::noViewMessage();
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível reordenar os princípios.');
        }
    }
}
