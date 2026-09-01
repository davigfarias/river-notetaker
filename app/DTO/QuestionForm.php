<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Question;
use Livewire\Attributes\Validate;
use Livewire\Form;

class QuestionForm extends Form
{
    #[Validate('required|string|max:2000')]
    public string $prompt = '';

    #[Validate('required|string|max:5000')]
    public string $referenceAnswer = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $keywords = null;

    #[Validate('boolean')]
    public bool $isCloze = false;

    public function fillFromQuestion(Question $question): void
    {
        $this->prompt = $question->prompt;
        $this->referenceAnswer = $question->reference_answer;
        $this->keywords = $question->keywords;
        $this->isCloze = $question->is_cloze;
    }

    public function toData(): QuestionData
    {
        return new QuestionData(
            prompt: $this->prompt,
            referenceAnswer: $this->referenceAnswer,
            keywords: $this->keywords ?: null,
            isCloze: $this->isCloze,
        );
    }
}
