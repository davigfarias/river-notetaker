<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

final readonly class CreateDiscipline
{
    public function __construct(
        private AppRepository $appRepository,
    ) {}

    public function handle(string $title, string $icon): Outcome
    {
        try {
            $this->appRepository
                ->createDiscipline($title, $icon);

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
