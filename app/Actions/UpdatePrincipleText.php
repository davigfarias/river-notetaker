<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\PrincipleTextForm;
use App\Enums\PrincipleType;
use App\Models\Principle;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdatePrincipleText
{
    public function handle(int $principleId, PrincipleTextForm $form): Outcome
    {
        try {
            $principle = Principle::where('type', PrincipleType::Text)->findOrFail($principleId);

            $principle->update([
                'title' => $form->title,
                'body' => $form->body,
            ]);

            return Outcome::success(message: 'Princípio atualizado.');
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar princípio: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar o princípio.');
        }
    }
}
