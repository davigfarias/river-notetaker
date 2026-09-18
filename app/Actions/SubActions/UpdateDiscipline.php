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

final readonly class UpdateDiscipline
{
    public function __construct(
        private ScheduleDisciplineReviews $scheduleDisciplineReviews,
    ) {}

    public function handle(DisciplinesDTO $dto): Outcome
    {
        try {
            $discipline = Disciplines::find($dto->id);

            if (! $discipline instanceof Disciplines) {
                return Outcome::failure('Disciplina não encontrada.');
            }

            $previousWeekday = $discipline->class_weekday;

            $discipline->update([
                'title' => $dto->title,
                'slug' => Str::slug((string) $dto->title),
                'icon' => $dto->icon,
                'period' => $dto->period,
                'code' => $dto->code,
                'professor' => $dto->professor,
                'class_weekday' => $dto->class_weekday,
                'completed_at' => match (true) {
                    ! $dto->is_completed => null,
                    $discipline->completed_at instanceof CarbonImmutable => $discipline->completed_at,
                    default => CarbonImmutable::now(),
                },
            ]);

            if ($discipline->class_weekday !== $previousWeekday) {
                $this->scheduleDisciplineReviews->handle($discipline->refresh());
            }

            return Outcome::success('Disciplina atualizada com sucesso!');
        } catch (UniqueConstraintViolationException $e) {
            Log::error("Tentativa de renomear disciplina para um título existente: {$e->getMessage()}");

            return Outcome::failure('Já existe uma disciplina cadastrada com este título.');
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure('Não foi possível atualizar a disciplina!');
        }
    }
}
