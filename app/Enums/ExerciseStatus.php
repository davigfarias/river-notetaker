<?php

declare(strict_types=1);

namespace App\Enums;

enum ExerciseStatus: string
{
    case Ready = 'ready';
    case Stale = 'stale';
    case Failed = 'failed';
}
