<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('concept_reviews')) {
            Schema::create('concept_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('access_token_id')->constrained('access_tokens', indexName: 'cr_token_foreign')->cascadeOnDelete();
                $table->foreignId('concept_id')->constrained('concepts', indexName: 'cr_concept_foreign')->cascadeOnDelete();
                $table->string('result', 16);
                $table->dateTime('reviewed_at');
                $table->timestamps();

                $table->index(['access_token_id', 'reviewed_at'], 'cr_token_reviewed_index');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
