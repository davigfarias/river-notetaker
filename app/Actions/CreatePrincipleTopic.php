<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\PrincipleTopicForm;
use App\Models\PrincipleTopic;
use App\Support\Outcome;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class CreatePrincipleTopic
{
    public function handle(PrincipleTopicForm $form): Outcome
    {
        try {
            $topic = PrincipleTopic::create([
                'title' => $form->title,
                'slug' => Str::slug($form->title),
            ]);

            return Outcome::success(message: "Tema '{$form->title}' criado com sucesso", data: $topic);
        } catch (UniqueConstraintViolationException $e) {
            Log::error("Tentativa de criar tema duplicado: {$e->getMessage()}");

            return Outcome::failure(message: 'Já existe um tema cadastrado com este título.');
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível criar o tema.');
        }
    }
}
