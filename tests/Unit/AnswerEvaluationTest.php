<?php

use App\DTO\AnswerEvaluation;
use App\Enums\AiVerdict;
use App\Enums\SelfRating;
use App\Exceptions\InvalidAiEvaluation;

function validEvaluation(array $override = []): array
{
    return array_merge([
        'verdict' => 'partial',
        'covered_points' => ['Citou a graça'],
        'missing_points' => ['Faltou a fé'],
        'misconceptions' => [],
        'feedback' => 'Bom começo, mas falta a fé.',
        'suggested_rating' => 'hard',
        'confidence' => 0.8,
    ], $override);
}

test('hidrata uma avaliação válida e devolve o mesmo array', function () {
    $evaluation = AnswerEvaluation::fromArray(validEvaluation());

    expect($evaluation->verdict)->toBe(AiVerdict::Partial)
        ->and($evaluation->suggestedRating)->toBe(SelfRating::Hard)
        ->and($evaluation->toArray())->toEqual(validEvaluation());
});

test('rejeita payloads inválidos com exceção de domínio', function (array $override) {
    AnswerEvaluation::fromArray(validEvaluation($override));
})->throws(InvalidAiEvaluation::class)->with([
    'veredito desconhecido' => [['verdict' => 'otimo']],
    'nota desconhecida' => [['suggested_rating' => 'perfeito']],
    'confiança acima de 1' => [['confidence' => 1.5]],
    'confiança negativa' => [['confidence' => -0.1]],
    'confiança não numérica' => [['confidence' => 'alta']],
    'feedback vazio' => [['feedback' => '   ']],
    'feedback gigante' => [['feedback' => str_repeat('a', 1201)]],
    'pontos não são lista' => [['covered_points' => 'texto solto']],
    'ponto não é texto' => [['missing_points' => [123]]],
    'pontos demais' => [['misconceptions' => array_fill(0, 9, 'x')]],
    'campo ausente' => [['covered_points' => null]],
]);

test('a confiança abaixo do limiar marca a avaliação como incerta', function () {
    expect(AnswerEvaluation::fromArray(validEvaluation(['confidence' => 0.5]))->isUncertain(0.6))->toBeTrue()
        ->and(AnswerEvaluation::fromArray(validEvaluation(['confidence' => 0.6]))->isUncertain(0.6))->toBeFalse();
});
