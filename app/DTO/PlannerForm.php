<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Planner;
use Livewire\Attributes\Validate;
use Livewire\Form;

class PlannerForm extends Form
{
    #[Validate('required|string|max:80', message: [
        'required' => 'Dê um nome ao planner.',
        'max' => 'O nome pode ter no máximo 80 caracteres.',
    ])]
    public string $name = '';

    #[Validate('nullable|string|max:500', message: [
        'max' => 'O objetivo pode ter no máximo 500 caracteres.',
    ])]
    public ?string $objective = null;

    #[Validate('nullable|date', message: [
        'date' => 'Escolha uma data válida ou deixe em branco.',
    ])]
    public ?string $targetDate = null;

    public function fillFromModel(Planner $planner): void
    {
        $this->name = $planner->name;
        $this->objective = $planner->objective;
        $this->targetDate = $planner->target_date?->toDateString();
    }

    /**
     * @return array{name: string, objective: string|null, target_date: string|null}
     */
    public function toAttributes(): array
    {
        return [
            'name' => trim($this->name),
            'objective' => filled($this->objective) ? trim((string) $this->objective) : null,
            'target_date' => filled($this->targetDate) ? $this->targetDate : null,
        ];
    }
}
