<?php

use App\Models\AccessToken;

test('o título da aba termina com o nome do app', function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    $this->get(route('principios'))->assertSee('<title>Princípios - '.config('app.name').'</title>', false);
    $this->get(route('principios.estudar'))->assertSee('<title>Estudar princípios - '.config('app.name').'</title>', false);
});

test('a tela de entrada também leva o nome do app no título, sem repetir', function () {
    $this->get(route('entrar'))->assertSee('<title>Entrar - '.config('app.name').'</title>', false);
});

test('toda página do app termina o título com o nome do app', function (string $route) {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    $this->get(route($route))->assertSee(config('app.name').'</title>', false);
})->with(['dashboard', 'concepts', 'pastoral', 'historico']);
