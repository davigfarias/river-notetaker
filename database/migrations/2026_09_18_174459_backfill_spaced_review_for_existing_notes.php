<?php

use App\Models\Notes;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Coloca na fila de revisão as notas escritas antes dela existir.
 *
 * Disciplinas sem dia de aula recebem o dia da semana em que a maioria das suas
 * notas foi escrita, já que a nota nasce na aula. Depois, toda nota escrita
 * antes de hoje e nunca revisada entra no primeiro degrau, cobrando na aula de
 * hoje ou na próxima. Isso inclui as que o formulário empurrou para o encontro
 * seguinte ao definir o dia de aula. Dia de aula já definido, notas atrasadas e
 * notas com histórico de revisão ficam como estão.
 */
return new class extends Migration
{
    public function up(): void
    {
        $today = CarbonImmutable::now()->startOfDay();

        DB::table('disciplines')
            ->whereNull('completed_at')
            ->orderBy('id')
            ->get(['id', 'class_weekday'])
            ->each(function (object $discipline) use ($today): void {
                $weekday = $discipline->class_weekday ?? $this->inferWeekday($discipline->id);

                if ($weekday === null) {
                    return;
                }

                if ($discipline->class_weekday === null) {
                    DB::table('disciplines')
                        ->where('id', $discipline->id)
                        ->update(['class_weekday' => $weekday]);
                }

                $dueAt = $today->addDays(((int) $weekday - $today->isoWeekday() + 7) % 7);

                DB::table('notes')
                    ->where('discipline_id', $discipline->id)
                    ->whereNull('consolidated_at')
                    ->where('created_at', '<', $today->toDateTimeString())
                    ->where(fn (Builder $query) => $query
                        ->whereNull('next_review_at')
                        ->orWhereDate('next_review_at', '>', $dueAt->toDateString()))
                    ->whereNotExists(fn (Builder $query) => $query
                        ->from('review_logs')
                        ->whereColumn('review_logs.reviewable_id', 'notes.id')
                        ->where('review_logs.reviewable_type', Notes::class))
                    ->update([
                        'review_stage' => 1,
                        'next_review_at' => $dueAt->toDateString(),
                    ]);
            });
    }

    public function down(): void
    {
        //
    }

    /**
     * Dia da semana ISO mais frequente entre as notas da disciplina. Empate fica
     * com o dia da nota mais recente.
     */
    private function inferWeekday(int $disciplineId): ?int
    {
        /** @var Collection<int, int> $weekdays */
        $weekdays = DB::table('notes')
            ->where('discipline_id', $disciplineId)
            ->orderByDesc('created_at')
            ->pluck('created_at')
            ->filter()
            ->map(fn (string $createdAt): int => CarbonImmutable::parse($createdAt)->isoWeekday())
            ->values();

        if ($weekdays->isEmpty()) {
            return null;
        }

        $counts = $weekdays->countBy();
        $highest = $counts->max();

        return $weekdays->first(fn (int $weekday): bool => $counts[$weekday] === $highest);
    }
};
