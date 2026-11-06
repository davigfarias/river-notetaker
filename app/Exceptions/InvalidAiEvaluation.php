<?php

declare(strict_types=1);

namespace App\Exceptions;

/** A IA devolveu algo que não obedece ao schema da avaliação. */
final class InvalidAiEvaluation extends \DomainException {}
