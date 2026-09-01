<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\AnswerDiff;
use App\DTO\AnswerSegment;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;
use Jfcherng\Diff\SequenceMatcher;
use Jfcherng\Diff\SequenceMatcherOptions;

final readonly class BuildAnswerDiffSegments
{
    /**
     * @param  array<int, string>  $referenceWords
     * @param  array<int, string>  $answerWords
     */
    public function handle(array $referenceWords, array $answerWords): Outcome
    {
        try {
            $matcher = new SequenceMatcher($referenceWords, $answerWords, options: new SequenceMatcherOptions(ignoreCase: true));

            $referenceSegments = [];
            $answerSegments = [];

            foreach ($matcher->getOpcodes() as [$op, $i1, $i2, $j1, $j2]) {
                $matched = $op === SequenceMatcher::OP_EQ;

                if ($i2 > $i1) {
                    $referenceSegments[] = new AnswerSegment($matched, implode(' ', array_slice($referenceWords, $i1, $i2 - $i1)));
                }

                if ($j2 > $j1) {
                    $answerSegments[] = new AnswerSegment($matched, implode(' ', array_slice($answerWords, $j1, $j2 - $j1)));
                }
            }

            return Outcome::noViewMessage(data: new AnswerDiff($referenceSegments, $answerSegments));
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível comparar as respostas.');
        }
    }
}
