<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Outcome;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Log;

final readonly class ValidateGeneratedQuestions
{
    public function __construct(
        #[Config('quiz.questions_per_session')] private int $minimumRequired = 4,
    ) {}

    /**
     * Descarta perguntas malformadas sem nova chamada de IA: campo vazio,
     * alternativa duplicada, ou distrator fora de uma faixa razoável de
     * tamanho comparado à resposta certa.
     *
     * @param  array<int, array{question?: string, correct_answer?: string, distractors?: array<int, string>}>  $questions
     * @return Outcome data: array<int, array{question: string, correct_answer: string, distractors: array<int, string>}>
     */
    public function handle(array $questions): Outcome
    {
        try {
            $valid = [];

            foreach ($questions as $item) {
                $cleaned = $this->clean($item);

                if ($cleaned !== null) {
                    $valid[] = $cleaned;
                }
            }

            if (count($valid) < $this->minimumRequired) {
                return Outcome::failure(message: 'A IA não gerou perguntas suficientes com qualidade adequada.');
            }

            return Outcome::noViewMessage(data: $valid);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível validar as perguntas geradas.');
        }
    }

    /**
     * @param  array{question?: string, correct_answer?: string, distractors?: array<int, string>}  $item
     * @return array{question: string, correct_answer: string, distractors: array<int, string>}|null
     */
    private function clean(array $item): ?array
    {
        $question = trim((string) ($item['question'] ?? ''));
        $correct = trim((string) ($item['correct_answer'] ?? ''));
        $distractors = array_map(fn ($d): string => trim((string) $d), $item['distractors'] ?? []);

        if ($question === '' || $correct === '' || count($distractors) !== 3) {
            return null;
        }

        if (in_array('', $distractors, true)) {
            return null;
        }

        $options = [$correct, ...$distractors];
        $normalized = array_map(fn (string $o): string => mb_strtolower($o), $options);

        if (count($normalized) !== count(array_unique($normalized))) {
            return null;
        }

        $correctLength = mb_strlen($correct);

        foreach ($distractors as $distractor) {
            $ratio = mb_strlen($distractor) / max($correctLength, 1);

            if ($ratio < 0.4 || $ratio > 2.5) {
                return null;
            }
        }

        return [
            'question' => $question,
            'correct_answer' => $correct,
            'distractors' => $distractors,
        ];
    }
}
