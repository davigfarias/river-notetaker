<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ClozeResultSegment;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class BuildClozeResultSegments
{
    public function __construct(
        private TokenizeAnswerText $tokenizeAnswerText,
    ) {}

    /**
     * Rebuild a cloze sentence for the results page: plain text for the words
     * that were shown, and the graded blank (what the user typed vs. what was
     * expected) for the words that were removed.
     *
     * @param  array<int, array{index: int, expected: string, given: string, correct: bool}>  $clozeBlanks
     * @return Outcome data: array<int, ClozeResultSegment>
     */
    public function handle(string $referenceAnswer, array $clozeBlanks): Outcome
    {
        try {
            $tokensOutcome = $this->tokenizeAnswerText->handle($referenceAnswer);

            if (! $tokensOutcome->success) {
                return $tokensOutcome;
            }

            $blanksByIndex = [];

            foreach ($clozeBlanks as $blank) {
                $blanksByIndex[$blank['index']] = $blank;
            }

            $segments = [];

            foreach ($tokensOutcome->data as $token) {
                $blank = $token['word'] ? ($blanksByIndex[$token['index']] ?? null) : null;

                if ($blank === null) {
                    $segments[] = new ClozeResultSegment(blank: false, text: $token['text']);

                    continue;
                }

                $segments[] = new ClozeResultSegment(
                    blank: true,
                    text: '',
                    given: $blank['given'],
                    expected: $blank['expected'],
                    correct: $blank['correct'],
                );
            }

            return Outcome::noViewMessage(data: $segments);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível montar o resultado do Cloze.');
        }
    }
}
