<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('concept_recall_counters')) {
            Schema::create('concept_recall_counters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('access_token_id')->constrained('access_tokens', indexName: 'crc_token_foreign')->cascadeOnDelete();
                $table->foreignId('concept_id')->constrained('concepts', indexName: 'crc_concept_foreign')->cascadeOnDelete();
                $table->unsignedInteger('aced_count')->default(0);
                $table->unsignedInteger('missed_count')->default(0);
                $table->dateTime('last_reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(['access_token_id', 'concept_id'], 'crc_token_concept_unique');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
