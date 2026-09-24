<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Coloca na fila de revisão as anotações de leitura escritas antes dela
 * existir. Sem "dia de aula" para esperar, elas entram direto no primeiro
 * degrau, vencendo hoje.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('reading_notes')
            ->whereNull('next_review_at')
            ->update([
                'review_stage' => 1,
                'next_review_at' => CarbonImmutable::now()->toDateString(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
