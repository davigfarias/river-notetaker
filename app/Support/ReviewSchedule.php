<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Weekday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Escada de repetição espaçada medida em aulas, não em dias.
 *
 * O relógio do semestre é o encontro semanal da disciplina: o estágio 1 cobra a
 * nota na aula seguinte, o 2 duas aulas depois, e assim por diante. Esgotada a
 * escada, a nota é considerada consolidada e sai da fila.
 */
final readonly class ReviewSchedule
{
    /**
     * Intervalo, em aulas, de cada estágio da escada.
     *
     * @var array<int, int>
     */
    public const array STEPS = [1 => 1, 2 => 2, 3 => 4, 4 => 8];

    public const int FINAL_STAGE = 4;

    /**
     * Estágio a que a nota vai depois da revisão: acertou avança um degrau,
     * travou volta para o primeiro.
     */
    public static function nextStage(int $currentStage, bool $recalled): int
    {
        if (! $recalled) {
            return 1;
        }

        return min(self::normalizeStage($currentStage) + 1, self::FINAL_STAGE + 1);
    }

    /**
     * Notas criadas antes da fila de revisão existir carregam estágio zero.
     * Elas valem como nunca revisadas, ou seja, o primeiro degrau da escada.
     */
    public static function normalizeStage(int $stage): int
    {
        return max(1, min($stage, self::FINAL_STAGE + 1));
    }

    public static function isConsolidated(int $stage): bool
    {
        return $stage > self::FINAL_STAGE;
    }

    /**
     * Data da aula em que a nota volta a ser cobrada, ou null quando ela já
     * percorreu a escada inteira.
     */
    public static function dueDateFor(int $stage, ?Weekday $classWeekday, CarbonInterface $from): ?CarbonImmutable
    {
        if (! $classWeekday instanceof Weekday || self::isConsolidated(self::normalizeStage($stage))) {
            return null;
        }

        $lessonsAhead = self::STEPS[self::normalizeStage($stage)] ?? 1;

        return $classWeekday
            ->onOrAfter(CarbonImmutable::instance($from)->startOfDay()->addDay())
            ->addWeeks($lessonsAhead - 1);
    }

    /**
     * Data da primeira cobrança de uma nota recém-criada: a próxima aula da
     * disciplina.
     */
    public static function firstDueDate(?Weekday $classWeekday, CarbonInterface $from): ?CarbonImmutable
    {
        return self::dueDateFor(1, $classWeekday, $from);
    }

    /**
     * Rótulo humano do estágio, usado nos cartões da fila do dia.
     */
    public static function stageLabel(int $stage): string
    {
        if (self::isConsolidated($stage)) {
            return 'Consolidada';
        }

        return match (self::normalizeStage($stage)) {
            1 => '1ª revisão',
            2 => '2ª revisão',
            3 => '3ª revisão',
            default => '4ª revisão',
        };
    }
}
