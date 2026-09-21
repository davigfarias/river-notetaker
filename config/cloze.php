<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cloze blank selection
    |--------------------------------------------------------------------------
    |
    | Tuning for App\Actions\SelectClozeBlanks: the share of eligible words
    | turned into blanks, the floor on how many blanks a sentence gets, and
    | the shortest word length still considered eligible.
    |
    */

    'blank_ratio' => 0.30,

    'min_blanks' => 1,

    'min_word_length' => 2,

    /*
    |--------------------------------------------------------------------------
    | Piso de acerto da revisão
    |--------------------------------------------------------------------------
    |
    | Percentual de lacunas que a revisão espaçada de notas exige para contar
    | como "lembrei" e subir um degrau da escada. Abaixo disso a nota volta ao
    | primeiro degrau.
    |
    */

    'pass_score' => 70,

];
