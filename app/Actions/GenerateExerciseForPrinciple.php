<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\PrincipleAiGateway;
use App\Ai\PromptVersions;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseStatus;
use App\Enums\ExerciseType;
use App\Exceptions\AiUnavailable;
use App\Exceptions\InvalidAiEvaluation;
use App\Exceptions\InvalidExercisePayload;
use App\Models\PrincipleExercise;
use App\Support\Outcome;
use Carbon\CarbonImmutable;

/**
 * Gera UM exercício com a IA e guarda no cache. Valida o resultado contra o escopo do usuário.
 * Resposta inválida ganha novas tentativas; esgotadas, grava uma linha Failed (nunca servida).
 * Falha de rede não grava nada: tenta de novo na próxima vez.
 */
final readonly class GenerateExerciseForPrinciple
{
    private const PREVIOUS_SCENARIOS = 5;

    public function __construct(
        private PrincipleAiGateway $gateway,
        private ReservePrincipleAiCall $reserve,
        private LoadStudyPrinciple $load,
        private FindNeighborPrinciples $neighbors,
    ) {}

    /**
     * Devolve o PrincipleExercise criado em `data`.
     */
    public function handle(int $accessTokenId, int $principleId, ExerciseType $type, ?CarbonImmutable $now = null): Outcome
    {
        $now ??= CarbonImmutable::now();

        if (! $type->requiresAiGeneration()) {
            return Outcome::failure('Este tipo de exercício não é gerado pela IA.');
        }

        if (! config('principios.ai.enabled')) {
            return Outcome::failure('A IA está desligada.');
        }

        $principle = $this->load->handle($principleId);
        $neighbors = $type === ExerciseType::DistinguishPrinciples ? $this->neighbors->handle($principleId)->all() : [];

        if ($type === ExerciseType::DistinguishPrinciples && $neighbors === []) {
            return Outcome::failure('Este princípio não tem vizinhos para montar o caso.');
        }

        if (! $this->reserve->handle($accessTokenId, $now)) {
            return Outcome::failure('Limite diário de chamadas à IA atingido.');
        }

        try {
            $payload = $this->generate($accessTokenId, $principle, $type, $neighbors, (int) config('principios.ai.max_retries'));
        } catch (AiUnavailable) {
            return Outcome::failure('A IA não respondeu agora.');
        } catch (InvalidExercisePayload|InvalidAiEvaluation) {
            return Outcome::failure('A IA devolveu um exercício inválido.', data: $this->record($accessTokenId, $principle, $type, [], ExerciseStatus::Failed, $now));
        }

        return Outcome::noViewMessage(data: $this->record($accessTokenId, $principle, $type, $payload, ExerciseStatus::Ready, $now));
    }

    /**
     * @param  array<int, StudyPrinciple>  $neighbors
     * @return array<string, mixed>
     *
     * @throws AiUnavailable
     * @throws InvalidExercisePayload
     */
    private function generate(int $accessTokenId, StudyPrinciple $principle, ExerciseType $type, array $neighbors, int $retriesLeft): array
    {
        try {
            return $this->once($accessTokenId, $principle, $type, $neighbors);
        } catch (InvalidExercisePayload $e) {
            return $retriesLeft > 0 ? $this->generate($accessTokenId, $principle, $type, $neighbors, $retriesLeft - 1) : throw $e;
        }
    }

    /**
     * @param  array<int, StudyPrinciple>  $neighbors
     * @return array<string, mixed>
     */
    private function once(int $accessTokenId, StudyPrinciple $principle, ExerciseType $type, array $neighbors): array
    {
        return match ($type) {
            ExerciseType::ApplyScenario => $this->gateway->generateScenario($principle, $this->previousScenarios($accessTokenId, $principle->id))->toArray(),
            ExerciseType::DistinguishPrinciples => $this->distinction($principle, $neighbors),
            ExerciseType::EvaluateInterpretation => $this->flawed($principle),
            default => throw new InvalidExercisePayload("Tipo {$type->value} não é gerado."),
        };
    }

    /**
     * @param  array<int, StudyPrinciple>  $neighbors
     * @return array<string, mixed>
     */
    private function distinction(StudyPrinciple $principle, array $neighbors): array
    {
        $payload = $this->gateway->generateDistinction($principle, $neighbors);
        $allowed = [$principle->id, ...array_map(fn (StudyPrinciple $n): int => $n->id, $neighbors)];

        if (array_diff($payload->candidateIds(), $allowed) !== [] || $payload->correctPrincipleId !== $principle->id) {
            throw new InvalidExercisePayload('O caso usa princípios fora do escopo ou aponta o princípio errado.');
        }

        return $payload->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function flawed(StudyPrinciple $principle): array
    {
        $payload = $this->gateway->generateFlawedInterpretation($principle);

        if ($payload->violatedPrincipleId !== $principle->id) {
            throw new InvalidExercisePayload('A interpretação aponta um princípio fora do escopo.');
        }

        return $payload->toArray();
    }

    /**
     * @return array<int, string>
     */
    private function previousScenarios(int $accessTokenId, int $principleId): array
    {
        return PrincipleExercise::query()
            ->where('access_token_id', $accessTokenId)
            ->where('principle_id', $principleId)
            ->where('type', ExerciseType::ApplyScenario)
            ->where('status', ExerciseStatus::Ready)
            ->latest('id')->limit(self::PREVIOUS_SCENARIOS)->get()
            ->map(fn (PrincipleExercise $e): string => (string) ($e->payload['scenario'] ?? ''))
            ->filter()->values()->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function record(int $accessTokenId, StudyPrinciple $principle, ExerciseType $type, array $payload, ExerciseStatus $status, CarbonImmutable $now): PrincipleExercise
    {
        return PrincipleExercise::query()->create([
            'access_token_id' => $accessTokenId,
            'principle_id' => $principle->id,
            'type' => $type,
            'bloom_level' => $type->bloomLevel(),
            'payload' => $payload,
            'source_hash' => $principle->sourceHash(),
            'prompt_version' => PromptVersions::for($type),
            'model' => (string) config('ai.default'),
            'status' => $status,
            'generated_at' => $now,
        ]);
    }
}
