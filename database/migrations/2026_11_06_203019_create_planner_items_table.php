<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Um item do planner é um capítulo de um livro ou, nas obras sem capítulos,
 * a obra inteira (`chapter_id` nulo). O check de leitura (`read_at`) é do
 * item: cada planner tem o seu, independente de qualquer outro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planner_id')->constrained('planners')->cascadeOnDelete();
            $table->foreignId('reference_material_id')->constrained('reference_materials')->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained('chapters')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['planner_id', 'position'], 'planner_items_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_items');
    }
};
