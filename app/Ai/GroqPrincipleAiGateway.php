<?php

declare(strict_types=1);

namespace App\Ai;

use App\Ai\Agents\DistinctionGenerator;
use App\Ai\Agents\FlawedInterpretationGenerator;
use App\Ai\Agents\PrincipleAnswerEvaluator;
use App\Ai\Agents\ScenarioGenerator;
use App\Ai\Prompts\EvaluationPrompt;
use App\Ai\Prompts\PrincipleAiRules;
use App\DTO\AnswerEvaluation;
use App\DTO\DistinctionPayload;
use App\DTO\FlawedInterpretationPayload;
use App\DTO\PreparedExercise;
use App\DTO\ScenarioPayload;
use App\DTO\StudyPrinciple;
use App\Exceptions\AiUnavailable;
use App\Exceptions\InvalidAiEvaluation;
use App\Exceptions\InvalidExercisePayload;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Random\Randomizer;

/** Usa o provedor padrão do Laravel AI SDK (Groq, ver config/ai.php). */
final readonly class GroqPrincipleAiGateway implements PrincipleAiGateway
{
    public function __construct(private Randomizer $randomizer) {}

    public function evaluateAnswer(PreparedExercise $exercise, StudyPrinciple $principle, string $userAnswer, ?string $chosenTitle = null): AnswerEvaluation
    {
        $data = $this->ask(new PrincipleAnswerEvaluator, EvaluationPrompt::build($exercise, $principle, $userAnswer, $chosenTitle), $principle->id, EvaluationPrompt::VERSION, InvalidAiEvaluation::class);

        $evaluation = AnswerEvaluation::fromArray($data);
        Log::info('Avaliação de princípio pela IA', ['principle_id' => $principle->id, 'verdict' => $evaluation->verdict->value, 'confidence' => $evaluation->confidence]);

        return $evaluation;
    }

    public function generateScenario(StudyPrinciple $principle, array $previousScenarios): ScenarioPayload
    {
        $previous = $previousScenarios === []
            ? '(nenhum)'
            : implode("\n---\n", array_map(PrincipleAiRules::safe(...), $previousScenarios));

        $prompt = $this->principleBlock('principio', $principle)."\n\n<cenarios_anteriores>\n{$previous}\n</cenarios_anteriores>";

        return ScenarioPayload::fromArray($this->ask(new ScenarioGenerator, $prompt, $principle->id, ScenarioGenerator::VERSION));
    }

    public function generateDistinction(StudyPrinciple $target, array $neighbors): DistinctionPayload
    {
        $list = implode("\n", array_map(fn (StudyPrinciple $n): string => "id {$n->id}: ".PrincipleAiRules::safe($n->title).' | '.PrincipleAiRules::safe($n->statement), $neighbors));
        $prompt = "{$this->principleBlock('principio_alvo', $target)}\n\n<vizinhos>\n{$list}\n</vizinhos>";

        $data = $this->ask(new DistinctionGenerator, $prompt, $target->id, DistinctionGenerator::VERSION);

        $candidates = array_map(fn (StudyPrinciple $p): array => ['principle_id' => $p->id, 'title' => $p->title], [$target, ...$neighbors]);

        // Os candidatos vêm de nós, nunca da IA; só o caso e a justificativa vêm dela.
        return DistinctionPayload::fromArray([
            'scenario' => $data['scenario'] ?? null,
            'rationale' => $data['rationale'] ?? null,
            'correct_principle_id' => $data['correct_principle_id'] ?? null,
            'candidates' => $this->randomizer->shuffleArray($candidates),
        ]);
    }

    public function generateFlawedInterpretation(StudyPrinciple $principle): FlawedInterpretationPayload
    {
        $data = $this->ask(new FlawedInterpretationGenerator, $this->principleBlock('principio', $principle), $principle->id, FlawedInterpretationGenerator::VERSION);

        // O princípio violado é sempre o alvo: não dependemos do que a IA diga.
        return FlawedInterpretationPayload::fromArray([...$data, 'violated_principle_id' => $principle->id]);
    }

    private function principleBlock(string $tag, StudyPrinciple $principle): string
    {
        $title = PrincipleAiRules::safe($principle->title);
        $statement = PrincipleAiRules::safe($principle->statement);
        $category = $principle->categoryTitle === '' ? '' : "\nCategoria: ".PrincipleAiRules::safe($principle->categoryTitle);

        return "<{$tag}>\nid: {$principle->id}\nTítulo: {$title}\nEnunciado: {$statement}{$category}\n</{$tag}>";
    }

    /**
     * @param  class-string<\Throwable>  $invalid  exceção lançada quando a resposta não é estruturada
     * @return array<string, mixed>
     */
    private function ask(PrincipleAnswerEvaluator|ScenarioGenerator|DistinctionGenerator|FlawedInterpretationGenerator $agent, string $prompt, int $principleId, string $promptVersion, string $invalid = InvalidExercisePayload::class): array
    {
        $startedAt = microtime(true);

        try {
            $response = $agent->prompt($prompt, timeout: (int) config('principios.ai.timeout_seconds'));
        } catch (\Throwable $e) {
            Log::warning('Falha da IA em princípios', ['principle_id' => $principleId, 'prompt_version' => $promptVersion, 'erro' => $e::class]);

            throw new AiUnavailable($e->getMessage(), previous: $e);
        }

        if (! $response instanceof StructuredAgentResponse) {
            throw new $invalid('A IA não devolveu resposta estruturada.');
        }

        Log::info('Chamada de IA em princípios', ['principle_id' => $principleId, 'prompt_version' => $promptVersion, 'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000)]);

        return $response->toArray();
    }
}
