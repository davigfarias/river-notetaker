<?php

use Illuminate\Support\Facades\Blade;

test('toda modal do Flux traz o indicador de rolagem por padrão', function () {
    $html = Blade::render('<flux:modal name="longa">conteúdo</flux:modal>');

    expect($html)->toContain('data-modal-scroll-hint')->toContain('Role para baixo');
});

test('o indicador pode ser desligado por modal com scroll-hint="false"', function () {
    $html = Blade::render('<flux:modal name="curta" :scroll-hint="false">conteúdo</flux:modal>');

    expect($html)->not->toContain('data-modal-scroll-hint');
});
