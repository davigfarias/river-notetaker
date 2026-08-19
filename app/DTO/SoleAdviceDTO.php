<?php

namespace App\DTO;

use Livewire\Attributes\Validate;
use Livewire\Form;

class SoleAdviceDTO extends Form
{
    #[Validate('required|min:3|max:100')]
    public string $category = '';

    #[Validate('required|min:5')]
    public string $advice = '';
}
