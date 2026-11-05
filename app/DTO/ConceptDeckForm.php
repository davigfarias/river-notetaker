<?php

declare(strict_types=1);

namespace App\DTO;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ConceptDeckForm extends Form
{
    #[Validate('required|string|max:60', message: [
        'required' => 'Digite um nome para o deck.',
        'max' => 'O nome do deck pode ter no máximo 60 caracteres.',
    ])]
    public string $name = '';
}
