<?php

namespace App\DTO;

use Livewire\Attributes\Validate;
use Livewire\Form;

class PrincipleTextForm extends Form
{
    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('required|string|min:5')]
    public string $body = '';
}
