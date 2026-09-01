<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;
use Normalizer;

final class TextNormalizer
{
    /**
     * Fold text for loose comparison: strip Latin accents (á → a, ç → c, ...),
     * lowercase, drop punctuation, and collapse whitespace. NFD splits each
     * accented letter into base + combining mark, which \p{Mn} then strips;
     * non-Latin scripts (Hebrew, Greek, ...) don't decompose this way and are
     * left untouched.
     */
    public static function fold(string $text): string
    {
        $decomposed = Normalizer::normalize($text, Normalizer::FORM_D) ?: $text;

        return (string) Str::of($decomposed)
            ->lower()
            ->replaceMatches('/\p{Mn}/u', '')
            ->replaceMatches('/[^\p{L}\p{N}\s]/u', '')
            ->squish();
    }
}
