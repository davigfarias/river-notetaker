<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nós vêm dos marcadores {{no: ...}} das notas (lidos na hora); aqui só
     * ficam a posição no canvas e as setas, ambas amarradas à chave do conceito.
     */
    public function up(): void
    {
        if (! Schema::hasTable('map_nodes')) {
            Schema::create('map_nodes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discipline_id')->constrained('disciplines')->cascadeOnDelete();
                $table->string('key', 120);
                $table->string('label');
                $table->float('x')->default(0);
                $table->float('y')->default(0);
                $table->timestamps();

                $table->unique(['discipline_id', 'key']);
            });
        }

        if (! Schema::hasTable('map_edges')) {
            Schema::create('map_edges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discipline_id')->constrained('disciplines')->cascadeOnDelete();
                $table->string('from_key', 120);
                $table->string('to_key', 120);
                $table->timestamps();

                $table->unique(['discipline_id', 'from_key', 'to_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('map_edges');
        Schema::dropIfExists('map_nodes');
    }
};
