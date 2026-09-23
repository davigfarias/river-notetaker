<?php

use App\Ai\Agents\QuizQuestionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // A modal de revisão dispara a geração do quiz assim que abre (fila
        // sync roda o job na hora). Sem esse fake, qualquer teste que chame
        // openReview() faria uma chamada real à Groq. Testes que precisam de
        // perguntas específicas chamam QuizQuestionGenerator::fake() de novo.
        QuizQuestionGenerator::fake([
            ['questions' => array_fill(0, (int) config('quiz.pool_size'), [
                'question' => 'Pergunta de teste?',
                'correct_answer' => 'Resposta certa',
                'distractors' => ['Distrator um', 'Distrator dois', 'Distrator três'],
                'explanation' => 'Explicação de teste.',
            ])],
        ]);
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function loginWithAccessToken(string $code)
{
    $page = visit('/entrar')->assertPresent('[data-flux-otp]');

    foreach (str_split($code) as $index => $digit) {
        $page->fill(sprintf('ui-otp > div:nth-of-type(%d) input', $index + 1), $digit);
    }

    return $page->wait(0.5);
}
