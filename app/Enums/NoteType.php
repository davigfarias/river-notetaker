<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Formato do corpo da nota: texto corrido (impressões + experiências) ou mapa mental.
 */
enum NoteType: string
{
    case Text = 'text';
    case MindMap = 'mind_map';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texto corrido',
            self::MindMap => 'Mapa mental',
        };
    }
}
