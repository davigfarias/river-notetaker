<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Outcome;
use Illuminate\Support\Facades\Log;
use Jfcherng\Diff\SequenceMatcher;
use Jfcherng\Diff\SequenceMatcherOptions;

final readonly class ComputeAnswerSimilarity
{
    /**
     * @param  array<int, string>  $referenceWords
     * @param  array<int, string>  $answerWords
     */
    public function handle(array $referenceWords, array $answerWords): Outcome
    {
        try {
            if ($referenceWords === [] && $answerWords === []) {
                return Outcome::noViewMessage(data: 100);
            }

            $matcher = new SequenceMatcher($referenceWords, $answerWords, options: new SequenceMatcherOptions(ignoreCase: true));

            $matched = array_sum(array_column($matcher->getMatchingBlocks(), 2));
            $total = count($referenceWords) + count($answerWords);

            return Outcome::noViewMessage(data: (int) round((2 * $matched / $total) * 100));
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível calcular a proximidade da resposta.');
        }
    }
}
