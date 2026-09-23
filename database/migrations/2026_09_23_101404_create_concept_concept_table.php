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
        Schema::create('concept_concept', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concept_id')->constrained('concepts')->cascadeOnDelete();
            $table->foreignId('related_concept_id')->constrained('concepts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['concept_id', 'related_concept_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('concept_concept');
    }
};
