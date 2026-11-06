<?php

declare(strict_types=1);

return [
    'session' => [
        'default_size' => 8,
        'max_new_per_day' => 3,
        // Fatia mínima de itens Aplicar/Analisar/Avaliar/Criar por sessão.
        'min_higher_order_ratio' => 0.5,
    ],

    'ai' => [
        'enabled' => (bool) env('PRINCIPIOS_AI_ENABLED', true),
        'stock_per_type' => 2,
        'max_retries' => 1,
        'timeout_seconds' => 30,
        // Abaixo disso a UI mostra "avaliação incerta" e não pré-seleciona o rating.
        'confidence_threshold' => 0.6,
        'evaluate_distinction_rationale' => true,
        'daily_limit_per_user' => 60,
    ],

    'scheduler' => [
        'initial_ease' => 2.5,
        'min_ease' => 1.3,
        'max_interval_days' => 365,
    ],
];
