<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\QuizQuestion;
use App\Support\Outcome;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final readonly class SelectQuizQuestionsForSession
{
    public function __construct(
        #[Config('quiz.questions_per_session')] private int $count = 4,
    ) {}

    /**
     * Sorteia um subconjunto do pool e embaralha as alternativas de cada
     * questão. Nunca inclui qual alternativa é a correta: isso vira estado
     * público do componente Livewire e ficaria visível no HTML.
     *
     * @param  Collection<int, QuizQuestion>  $pool
     * @return Outcome data: array<int, array{id: int, question: string, options: array<int, string>}>
     */
    public function handle(Collection $pool): Outcome
    {
        try {
            $session = $pool
                ->shuffle()
                ->take($this->count)
                ->map(fn (QuizQuestion $question): array => [
                    'id' => $question->id,
                    'question' => $question->question,
                    'options' => collect([$question->correct_answer, ...$question->distractors])
                        ->shuffle()
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all();

            return Outcome::noViewMessage(data: $session);
        } catch (\Throwable $e) {
            Log::error(self::class.': '.$e->getMessage());

            return Outcome::failure(message: 'Não foi possível montar as perguntas desta revisão.');
        }
    }
}
