<?php

namespace App\DTO;

use Livewire\Attributes\Validate;
use Livewire\Form;

class SoleConceptDTO extends Form
{
    #[Validate('required|min:5|max:100')]
    public string $term = '';

    #[Validate('required|min:5')]
    public string $definition = '';
}
