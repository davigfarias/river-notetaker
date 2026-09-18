<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\DTO\DisciplinesDTO;
use App\Models\Disciplines;
use App\Support\Outcome;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class CreateDiscipline
{
    public function handle(DisciplinesDTO $dto): Outcome
    {
        try {
            Disciplines::create([
                'title' => $dto->title,
                'slug' => Str::slug((string) $dto->title),
                'icon' => $dto->icon,
                'period' => $dto->period,
                'code' => $dto->code,
                'professor' => $dto->professor,
                'class_weekday' => $dto->class_weekday,
                'completed_at' => $dto->is_completed ? CarbonImmutable::now() : null,
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
