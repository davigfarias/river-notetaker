<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Outcome;
use App\Support\TextNormalizer;
use Illuminate\Support\Facades\Log;

final readonly class NormalizeAnswerText
{
    public function handle(string $text): Outcome
    {
        try {
            // Fold Latin accents so a Portuguese answer typed without
            // diacritics isn't penalized against a reference answer that has
            // them, then split into comparable word tokens.
            $normalized = TextNormalizer::fold($text);

            return Outcome::noViewMessage(data: $normalized === '' ? [] : explode(' ', $normalized));
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível normalizar o texto da resposta.');
        }
    }
}
