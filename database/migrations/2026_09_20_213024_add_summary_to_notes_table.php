<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O resumo que o aluno escreve à mão, no espírito do Cornell. É ele que a
     * revisão espaçada cobra em lacunas; o resumo por IA fica só como conferência.
     */
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->text('summary')->nullable()->after('life_experiences');
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('summary');
        });
    }
};
