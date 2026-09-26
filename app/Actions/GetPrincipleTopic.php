<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PrincipleTopic;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetPrincipleTopic
{
    public function handle(string $slug): Outcome
    {
        try {
            $topic = PrincipleTopic::where('slug', $slug)
                ->with('principles.concept', 'principles.noteLinks.note.discipline', 'disciplines')
                ->first();

            return Outcome::noViewMessage(data: $topic);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar tema: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar o tema.');
        }
    }
}
