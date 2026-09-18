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
        Schema::create('reading_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_material_id')->constrained('reference_materials')->cascadeOnDelete();
            $table->foreignId('access_token_id')->nullable()->constrained('access_tokens')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('location')->nullable();
            $table->text('tags')->nullable();
            $table->unsignedInteger('page_snapshot')->nullable();
            $table->timestamps();

            $table->index('access_token_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_notes');
    }
};
