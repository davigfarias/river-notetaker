<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\PrincipleTextForm;
use App\Enums\PrincipleType;
use App\Models\Principle;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class AddTextPrinciple
{
    public function handle(int $principleTopicId, PrincipleTextForm $form): Outcome
    {
        try {
            Principle::create([
                'principle_topic_id' => $principleTopicId,
                'position' => Principle::where('principle_topic_id', $principleTopicId)->count(),
                'type' => PrincipleType::Text,
                'title' => $form->title,
                'body' => $form->body,
            ]);

            return Outcome::success(message: 'Princípio adicionado ao tema.');
        } catch (\Exception $e) {
            Log::error("Erro ao adicionar princípio: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível adicionar o princípio.');
        }
    }
}
