<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\Models\Disciplines;
use App\Support\Outcome;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class CreateDiscipline
{
    public function handle(string $title, string $icon): Outcome
    {
        try {
            Disciplines::create([
                'title' => $title,
                'slug' => Str::slug($title),
                'icon' => $icon,
            ]);

            return Outcome::success('Agora ela estará disponível no seu painel');
        } catch (UniqueConstraintViolationException $e) {
            Log::error("Tentativa de criar disciplina duplicada: {$e->getMessage()}");

            return Outcome::failure('Já existe uma disciplina cadastrada com este título.');
        } catch (\Exception $e) {
            Log::error('Erro: '.$e->getMessage());

            return Outcome::failure('Desconhecido ao tentar criar a disciplina!');
        }
    }
}
