<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class TokenizeAnswerText
{
    /**
     * Split text into an ordered list of tokens. A "word" token starts with a
     * letter and carries a running 0-based index; every other run of
     * characters (spaces, punctuation) is a "gap" token with a null index.
     * Concatenating every token's text reproduces the original string exactly,
     * which is what lets the cloze view rebuild the sentence with blanks.
     */
    public function handle(string $text): Outcome
    {
        try {
            preg_match_all('/\p{L}[\p{L}\p{N}\x27\-]*|[^\p{L}]+/u', $text, $matches);

            $tokens = [];
            $wordIndex = 0;

            foreach ($matches[0] as $piece) {
                $isWord = preg_match('/^\p{L}/u', $piece) === 1;

                $tokens[] = [
                    'word' => $isWord,
                    'text' => $piece,
                    'index' => $isWord ? $wordIndex : null,
                ];

                if ($isWord) {
                    $wordIndex++;
                }
            }

            return Outcome::noViewMessage(data: $tokens);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível dividir o texto da resposta.');
        }
    }
}
