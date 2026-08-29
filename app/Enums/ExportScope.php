<?php

declare(strict_types=1);

namespace App\Enums;

enum ExportScope: string
{
    case Reference = 'reference';
    case Search = 'search';
}
