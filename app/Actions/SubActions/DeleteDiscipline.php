<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final readonly class DeleteDiscipline
{
    public function __construct(
        private AppRepository $appRepository,
    ) {}

    public function handle(int $id): Outcome
    {
        try {
            $this->appRepository->deleteDisciplines($id);

            Cache::forget('disciplines');

            return Outcome::success('Disciplina removida com sucesso!');
        } catch (QueryException $e) {
            Log::error("Erro: {$e->getMessage()}");

            if ($e->getCode() === '23000') {
                return Outcome::failure('Não é possível excluir esta disciplina, pois existem outros registros vinculados a ela.');
            }
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure('Não é possível deletar a disciplina');
        }
    }
}
