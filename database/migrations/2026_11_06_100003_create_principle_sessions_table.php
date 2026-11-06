<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('principle_sessions')) {
            Schema::create('principle_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('access_token_id')->constrained('access_tokens', indexName: 'pse_token_foreign')->cascadeOnDelete();
                $table->foreignId('discipline_id')->nullable()->constrained('disciplines', indexName: 'pse_discipline_foreign')->nullOnDelete();
                $table->foreignId('principle_category_id')->nullable()->constrained('principle_categories', indexName: 'pse_category_foreign')->nullOnDelete();
                $table->unsignedSmallInteger('planned_items');
                $table->dateTime('started_at');
                $table->dateTime('finished_at')->nullable();
                $table->json('composition');
                $table->timestamps();

                $table->index(['access_token_id', 'finished_at'], 'pse_token_finished_index');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
