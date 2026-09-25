<?php

namespace App\DTO;

use Livewire\Attributes\Validate;
use Livewire\Form;

class PrincipleTopicForm extends Form
{
    #[Validate('required|string|min:3|max:255')]
    public string $title = '';
}
