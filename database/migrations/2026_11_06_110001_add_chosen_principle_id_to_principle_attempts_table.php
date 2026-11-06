<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('principle_attempts') && ! Schema::hasColumn('principle_attempts', 'chosen_principle_id')) {
            Schema::table('principle_attempts', function (Blueprint $table) {
                // Opção escolhida no exercício "Distinguir princípios". Sem FK: é só registro do que o usuário marcou.
                $table->unsignedBigInteger('chosen_principle_id')->nullable()->after('user_answer');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
