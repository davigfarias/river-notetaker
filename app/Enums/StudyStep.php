<?php

declare(strict_types=1);

namespace App\Enums;

enum StudyStep: string
{
    case Setup = 'setup';
    case Question = 'question';
    case Feedback = 'feedback';
    case Summary = 'summary';
}
