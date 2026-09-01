<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Outcome;
use App\Support\TextNormalizer;
use Illuminate\Support\Facades\Log;

final readonly class GradeClozeBlanks
{
    /**
     * Compare the words the user typed into each blank against the words that
     * were removed. Matching is accent- and case-insensitive (via
     * TextNormalizer::fold). Returns the percentage of blanks filled correctly
     * plus a per-blank breakdown for the results page.
     *
     * @param  array<int, array{word: bool, text: string, index: int|null}>  $tokens
     * @param  array<int, int>  $blankIndices
     * @param  array<int|string, string>  $given  keyed by word index
     * @return Outcome data: array{score: int, blanks: array<int, array{index: int, expected: string, given: string, correct: bool}>}
     */
    public function handle(array $tokens, array $blankIndices, array $given): Outcome
    {
        try {
            $wordsByIndex = [];

            foreach ($tokens as $token) {
                if ($token['word']) {
                    $wordsByIndex[$token['index']] = $token['text'];
                }
            }

            $blanks = [];
            $correctCount = 0;

            foreach ($blankIndices as $index) {
                $expected = $wordsByIndex[$index] ?? '';
                $givenValue = trim((string) ($given[$index] ?? ''));

                $foldedExpected = TextNormalizer::fold($expected);
                $correct = $foldedExpected !== '' && $foldedExpected === TextNormalizer::fold($givenValue);

                if ($correct) {
                    $correctCount++;
                }

                $blanks[] = [
                    'index' => $index,
                    'expected' => $expected,
                    'given' => $givenValue,
                    'correct' => $correct,
                ];
            }

            $score = $blankIndices === []
                ? 100
                : (int) round($correctCount / count($blankIndices) * 100);

            return Outcome::noViewMessage(data: [
                'score' => $score,
                'blanks' => $blanks,
            ]);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível corrigir as lacunas do Cloze.');
        }
    }
}
