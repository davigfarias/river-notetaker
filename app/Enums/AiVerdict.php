<?php

declare(strict_types=1);

namespace App\Enums;

enum AiVerdict: string
{
    case Correct = 'correct';
    case Partial = 'partial';
    case Incorrect = 'incorrect';
}
