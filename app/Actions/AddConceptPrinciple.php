<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PrincipleType;
use App\Models\Principle;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class AddConceptPrinciple
{
    public function handle(int $principleTopicId, int $conceptId): Outcome
    {
        try {
            $exists = Principle::query()
                ->where('principle_topic_id', $principleTopicId)
                ->where('concept_id', $conceptId)
                ->exists();

            if ($exists) {
                return Outcome::failure(message: 'Este conceito já está neste tema.');
            }

            Principle::create([
                'principle_topic_id' => $principleTopicId,
                'position' => Principle::where('principle_topic_id', $principleTopicId)->count(),
                'type' => PrincipleType::Concept,
                'concept_id' => $conceptId,
            ]);

            return Outcome::success(message: 'Conceito adicionado ao tema.');
        } catch (\Exception $e) {
            Log::error("Erro ao adicionar conceito ao tema: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível adicionar o conceito.');
        }
    }
}
