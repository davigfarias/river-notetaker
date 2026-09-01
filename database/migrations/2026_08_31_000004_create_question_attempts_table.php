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
        Schema::create('question_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('access_token_id')->nullable()->constrained('access_tokens')->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->json('cloze_blanks')->nullable();
            $table->boolean('skipped')->default(false);
            $table->timestamps();

            $table->index('access_token_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_attempts');
    }
};
