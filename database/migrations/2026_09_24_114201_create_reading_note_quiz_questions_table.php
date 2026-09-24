<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reading_note_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_note_id')->constrained('reading_notes')->cascadeOnDelete();
            $table->text('question');
            $table->string('correct_answer');
            $table->json('distractors');
            $table->string('content_hash');
            $table->string('provider')->default('groq');
            $table->string('model')->nullable();
            $table->timestamps();

            $table->index(['reading_note_id', 'content_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_note_quiz_questions');
    }
};
