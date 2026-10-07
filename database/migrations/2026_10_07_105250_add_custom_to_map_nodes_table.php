<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nó livre: criado direto no canvas, sem marcador {{no: ...}} nas notas.
     */
    public function up(): void
    {
        if (Schema::hasColumn('map_nodes', 'custom')) {
            return;
        }

        Schema::table('map_nodes', function (Blueprint $table) {
            $table->boolean('custom')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('map_nodes', function (Blueprint $table) {
            $table->dropColumn('custom');
        });
    }
};
