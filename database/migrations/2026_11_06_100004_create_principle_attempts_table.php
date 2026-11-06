<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('principle_attempts')) {
            Schema::create('principle_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('access_token_id')->constrained('access_tokens', indexName: 'pat_token_foreign')->cascadeOnDelete();
                $table->foreignId('principle_session_id')->constrained('principle_sessions', indexName: 'pat_session_foreign')->cascadeOnDelete();
                $table->foreignId('principle_id')->constrained('principles', indexName: 'pat_principle_foreign')->cascadeOnDelete();
                $table->foreignId('principle_exercise_id')->nullable()->constrained('principle_exercises', indexName: 'pat_exercise_foreign')->nullOnDelete();
                $table->string('type');
                $table->string('bloom_level');
                $table->text('user_answer')->nullable();
                $table->json('ai_evaluation')->nullable();
                $table->string('ai_verdict')->nullable();
                $table->string('self_rating')->nullable();
                $table->string('ai_suggested_rating')->nullable();
                $table->unsignedInteger('duration_ms')->default(0);
                $table->dateTime('answered_at');
                $table->timestamps();

                $table->index(['principle_id', 'bloom_level', 'answered_at'], 'pat_principle_bloom_answered_index');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
