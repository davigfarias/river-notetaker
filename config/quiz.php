<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Pool de perguntas
    |--------------------------------------------------------------------------
    |
    | Quantas perguntas o QuizQuestionGenerator gera de uma vez por resumo
    | (cacheadas por App\Actions\ResolveNoteQuizPool), e quantas delas entram
    | em cada abertura da modal de revisão (App\Actions\SelectQuizQuestionsForSession).
    | Sortear um subconjunto do pool a cada abertura evita repetir a mesma
    | prova, sem precisar chamar a IA de novo.
    |
    */

    'pool_size' => 8,

    'questions_per_session' => 4,

    /*
    |--------------------------------------------------------------------------
    | Piso de acerto da revisão
    |--------------------------------------------------------------------------
    |
    | Percentual de perguntas que a revisão espaçada de notas exige para
    | contar como "lembrei" e subir um degrau da escada. Abaixo disso a nota
    | volta ao primeiro degrau.
    |
    */

    'pass_score' => 70,

    /*
    |--------------------------------------------------------------------------
    | Job de geração
    |--------------------------------------------------------------------------
    |
    | Timeout do job (segundos) e intervalo de polling da modal enquanto o
    | pool ainda está sendo gerado (mesmo padrão de config/summarizer.php).
    |
    */

    'job_timeout' => 60,

    'poll_interval' => '2s',

];
