<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\PrincipleAttempt;

/**
 * Resultado de gravar uma resposta. `notice` explica, em português simples, por que a IA
 * não avaliou (desligada, limite do dia ou falha); nulo quando tudo correu bem.
 */
final readonly class SubmittedAnswer
{
    public function __construct(
        public PrincipleAttempt $attempt,
        public ?string $notice = null,
    ) {}
}
