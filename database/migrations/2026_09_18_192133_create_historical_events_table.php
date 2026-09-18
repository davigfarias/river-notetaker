<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_token_id')->nullable()->constrained('access_tokens')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('nature');
            $table->unsignedInteger('start_year');
            $table->string('start_era');
            $table->unsignedInteger('end_year')->nullable();
            $table->string('end_era')->nullable();
            $table->integer('sort_key');
            $table->timestamps();

            $table->index(['access_token_id', 'sort_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_events');
    }
};
