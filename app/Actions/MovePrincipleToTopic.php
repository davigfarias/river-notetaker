<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Principle;
use App\Models\PrincipleTopic;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class MovePrincipleToTopic
{
    /**
     * Move o princípio para o fim da lista sem categoria de outro tema.
     */
    public function handle(Principle $principle, PrincipleTopic $target): Outcome
    {
        try {
            if ($principle->principle_topic_id === $target->id) {
                return Outcome::failure(message: 'O princípio já está neste tema.');
            }

            if ($principle->concept_id !== null && $target->principles()->where('concept_id', $principle->concept_id)->exists()) {
                return Outcome::failure(message: 'Este conceito já está no tema de destino.');
            }

            $principle->update([
                'principle_topic_id' => $target->id,
                'principle_category_id' => null,
                'position' => $target->principles()->whereNull('principle_category_id')->count(),
            ]);

            return Outcome::success(message: "Movido para {$target->title}.");
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível mover o princípio.');
        }
    }
}
