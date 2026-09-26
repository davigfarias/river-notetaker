<?php

declare(strict_types=1);

namespace App\Enums;

enum NoteAnnotatableField: string
{
    case Summary = 'summary';
    case Impressions = 'impressions';
    case LifeExperiences = 'life_experiences';
}
