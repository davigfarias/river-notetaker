<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\PrincipleTopicForm;
use App\Models\PrincipleTopic;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class CreatePrincipleCategory
{
    public function handle(PrincipleTopic $topic, PrincipleTopicForm $form): Outcome
    {
        try {
            $category = $topic->categories()->create([
                'title' => $form->title,
                'position' => $topic->categories()->count(),
            ]);

            return Outcome::success(message: "Categoria '{$form->title}' criada.", data: $category);
        } catch (\Exception $e) {
            Log::error("Erro ao criar categoria: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível criar a categoria.');
        }
    }
}
