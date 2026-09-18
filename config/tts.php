<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Provider e modelo
    |--------------------------------------------------------------------------
    |
    | A Groq não faz TTS (o provider não implementa AudioProvider) e os modelos
    | de TTS dela só falam inglês e árabe, por isso a locução usa o Gemini
    | enquanto o resto da aplicação continua na Groq.
    |
    */

    'provider' => env('TTS_PROVIDER', 'gemini'),

    'model' => env('TTS_MODEL', 'gemini-2.5-flash-preview-tts'),

    /*
    |--------------------------------------------------------------------------
    | Voz e direção de locução
    |--------------------------------------------------------------------------
    |
    | `voice` é uma das vozes pré-definidas do Gemini (Kore, Puck, Sulafat...).
    | O gateway prefixa `instructions` ao texto, então elas funcionam como
    | direção para o narrador, não como conteúdo lido.
    |
    */

    'voice' => env('TTS_VOICE', 'Kore'),

    'instructions' => 'Leia o texto a seguir em português do Brasil, com ritmo calmo e '
        .'natural, como um narrador de audiolivro. Não comente nem anuncie nada.',

    /*
    |--------------------------------------------------------------------------
    | Limites
    |--------------------------------------------------------------------------
    |
    | O free tier do Gemini permite 10 gerações por dia por modelo, e uma
    | geração leva de 50 a 70 segundos. Por isso o áudio é gerado sob demanda,
    | numa fila, e fica guardado: o mesmo resumo nunca gasta cota duas vezes.
    |
    | `max_characters` protege a cota de textos longos, que além de caros
    | costumam voltar em erro. Ver os limites oficiais em:
    | https://ai.google.dev/gemini-api/docs/rate-limits
    |
    */

    'max_characters' => 700,

    'request_timeout' => 150,

    'job_timeout' => 180,

    'poll_interval' => '5s',

    'browser_cache_seconds' => 31536000,
];
