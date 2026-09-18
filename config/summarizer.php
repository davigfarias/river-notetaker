<?php

declare(strict_types=1);

return [
    'job_timeout' => 60,
    'poll_interval' => '2s',

    /*
    |--------------------------------------------------------------------------
    | Tamanho do resumo
    |--------------------------------------------------------------------------
    |
    | O resumo é um TL;DR escrito para ser ouvido em voz alta, então precisa
    | ser curto. `target_characters` é o teto pedido ao modelo; `max_characters`
    | é o teto tolerado pelo código, com folga porque modelos de linguagem não
    | contam caracteres com precisão. Passando do tolerado, o job pede ao
    | modelo que encurte uma vez antes de aceitar o texto como veio.
    |
    */

    'target_characters' => 300,

    'max_characters' => 500,
];
