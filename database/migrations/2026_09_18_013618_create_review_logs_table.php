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
        Schema::create('review_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('reviewable');
            $table->foreignId('access_token_id')->nullable()->constrained('access_tokens')->nullOnDelete();
            $table->boolean('recalled');
            $table->unsignedTinyInteger('stage_before');
            $table->unsignedTinyInteger('stage_after');
            $table->date('due_at')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index('access_token_id');
            $table->index('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_logs');
    }
};
