<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Outcome;
use App\Support\PortugueseStopwords;
use App\Support\TextNormalizer;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Log;

final readonly class SelectClozeBlanks
{
    public function __construct(
        #[Config('cloze.blank_ratio')] private float $blankRatio = 0.30,
        #[Config('cloze.min_blanks')] private int $minBlanks = 1,
        #[Config('cloze.min_word_length')] private int $minWordLength = 2,
    ) {}

    /**
     * Pick which word indices to turn into cloze blanks. Eligible words are
     * long enough, non-numeric, and not Portuguese function words. Roughly
     * `cloze.blank_ratio` of the eligible words are chosen at random (at
     * least `cloze.min_blanks`). Returns a sorted list of word indices, or an
     * empty list when nothing is eligible.
     *
     * @param  array<int, array{word: bool, text: string, index: int|null}>  $tokens
     * @return Outcome data: array<int, int>
     */
    public function handle(array $tokens, ?float $ratio = null): Outcome
    {
        try {
            $ratio ??= $this->blankRatio;

            $eligible = [];

            foreach ($tokens as $token) {
                if (! $token['word']) {
                    continue;
                }

                $folded = TextNormalizer::fold($token['text']);

                if (mb_strlen($folded) < $this->minWordLength) {
                    continue;
                }

                if (ctype_digit($folded)) {
                    continue;
                }

                if (PortugueseStopwords::contains($token['text'])) {
                    continue;
                }

                $eligible[] = $token['index'];
            }

            if ($eligible === []) {
                return Outcome::noViewMessage(data: []);
            }

            $count = min(
                count($eligible),
                max($this->minBlanks, (int) round(count($eligible) * $ratio)),
            );

            shuffle($eligible);
            $chosen = array_slice($eligible, 0, $count);
            sort($chosen);

            return Outcome::noViewMessage(data: $chosen);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível selecionar as lacunas do Cloze.');
        }
    }
}
