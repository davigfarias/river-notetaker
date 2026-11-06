<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExerciseStatus;
use App\Models\PrincipleExercise;

/**
 * Marca como Stale os exercícios gerados a partir de um texto antigo do princípio.
 * Chamada quando o princípio é editado; o cache também confere o hash ao servir, por segurança.
 */
final readonly class InvalidateStaleExercises
{
    public function __construct(private LoadStudyPrinciple $load) {}

    public function handle(int $principleId): int
    {
        return PrincipleExercise::query()
            ->where('principle_id', $principleId)
            ->where('status', ExerciseStatus::Ready)
            ->where('source_hash', '!=', $this->load->handle($principleId)->sourceHash())
            ->update(['status' => ExerciseStatus::Stale]);
    }
}
