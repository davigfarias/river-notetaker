<?php

declare(strict_types=1);

namespace App\Exceptions;

/** O exercício gerado pela IA (ou lido do cache) não obedece ao formato esperado. */
final class InvalidExercisePayload extends \DomainException {}
