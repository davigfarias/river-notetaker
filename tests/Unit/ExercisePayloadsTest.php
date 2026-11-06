<?php

use App\DTO\DistinctionPayload;
use App\DTO\FlawedInterpretationPayload;
use App\DTO\ScenarioPayload;
use App\Exceptions\InvalidExercisePayload;

test('ScenarioPayload hidrata e devolve o mesmo array', function () {
    $data = ['scenario' => 'Um pastor atende um jovem em dúvida.', 'question' => 'Como aplicar?', 'expected_application' => 'Apontar para Cristo.', 'key_points' => ['Cristo', 'Graça']];

    expect(ScenarioPayload::fromArray($data)->toArray())->toEqual($data);
});

test('payloads inválidos lançam exceção de domínio', function (string $class, array $data) {
    $class::fromArray($data);
})->throws(InvalidExercisePayload::class)->with([
    'cenário vazio' => [ScenarioPayload::class, ['scenario' => ' ', 'question' => 'q', 'expected_application' => 'e', 'key_points' => []]],
    'pontos-chave demais' => [ScenarioPayload::class, ['scenario' => 's', 'question' => 'q', 'expected_application' => 'e', 'key_points' => array_fill(0, 7, 'x')]],
    'interpretação ausente' => [FlawedInterpretationPayload::class, ['flaw_explanation' => 'f', 'violated_principle_id' => 1]],
    'id não numérico' => [FlawedInterpretationPayload::class, ['interpretation' => 'i', 'flaw_explanation' => 'f', 'violated_principle_id' => 'abc']],
    'correto fora dos candidatos' => [DistinctionPayload::class, ['scenario' => 's', 'rationale' => 'r', 'correct_principle_id' => 9, 'candidates' => [['principle_id' => 1, 'title' => 'A'], ['principle_id' => 2, 'title' => 'B']]]],
    'só um candidato' => [DistinctionPayload::class, ['scenario' => 's', 'rationale' => 'r', 'correct_principle_id' => 1, 'candidates' => [['principle_id' => 1, 'title' => 'A']]]],
    'candidato malformado' => [DistinctionPayload::class, ['scenario' => 's', 'rationale' => 'r', 'correct_principle_id' => 1, 'candidates' => ['x', 'y']]],
]);

test('DistinctionPayload expõe os ids dos candidatos', function () {
    $payload = DistinctionPayload::fromArray(['scenario' => 's', 'rationale' => 'r', 'correct_principle_id' => 2, 'candidates' => [['principle_id' => 1, 'title' => 'A'], ['principle_id' => 2, 'title' => 'B']]]);

    expect($payload->candidateIds())->toBe([1, 2]);
});
