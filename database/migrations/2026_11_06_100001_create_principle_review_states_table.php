<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('principle_review_states')) {
            Schema::create('principle_review_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('access_token_id')->constrained('access_tokens', indexName: 'prs_token_foreign')->cascadeOnDelete();
                $table->foreignId('principle_id')->constrained('principles', indexName: 'prs_principle_foreign')->cascadeOnDelete();
                $table->dateTime('due_at')->index('prs_due_at_index');
                $table->dateTime('last_reviewed_at')->nullable();
                $table->unsignedSmallInteger('interval_days')->default(0);
                $table->decimal('ease', 4, 2)->default(2.5);
                $table->unsignedSmallInteger('repetitions')->default(0);
                $table->unsignedSmallInteger('lapses')->default(0);
                $table->string('last_rating')->nullable();
                $table->timestamps();

                $table->unique(['access_token_id', 'principle_id'], 'prs_token_principle_unique');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
