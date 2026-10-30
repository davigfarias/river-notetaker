<?php

use Illuminate\Support\Facades\Blade;

function renderQuizResults(array $results): string
{
    return Blade::render('<x-quiz-results :results="$results" />', ['results' => $results]);
}

it('separa acertos e erros em dois cartões e só pede o porquê nos acertos', function () {
    $html = renderQuizResults([
        ['question' => 'Q1', 'chosen' => 'certa', 'correct' => true, 'correct_answer' => 'certa'],
        ['question' => 'Q2', 'chosen' => 'errada', 'correct' => false, 'correct_answer' => 'gabarito'],
    ]);

    expect($html)
        ->toContain('Acertos (1)')
        ->toContain('Erros (1)')
        ->toContain('Por que essa é a resposta correta?')
        ->toContain('gabarito')
        ->and(substr_count($html, '<textarea'))->toBe(1);
});

it('mostra mensagem explícita quando um dos cartões está vazio', function () {
    expect(renderQuizResults([
        ['question' => 'Q1', 'chosen' => 'a', 'correct' => true, 'correct_answer' => 'a'],
    ]))->toContain('Nenhum erro');

    expect(renderQuizResults([
        ['question' => 'Q1', 'chosen' => '', 'correct' => false, 'correct_answer' => 'b'],
    ]))->toContain('Nenhuma resposta correta');
});
