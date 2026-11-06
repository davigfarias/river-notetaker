<?php

declare(strict_types=1);

namespace App\Exceptions;

/** Timeout, provedor fora do ar ou qualquer falha de rede ao falar com a IA. */
final class AiUnavailable extends \RuntimeException {}
