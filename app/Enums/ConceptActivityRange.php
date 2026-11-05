<?php

declare(strict_types=1);

namespace App\Enums;

enum ConceptActivityRange: string
{
    case Week = '7d';
    case Month = '30d';
    case All = 'all';
}
