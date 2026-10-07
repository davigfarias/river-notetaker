<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Curvatura da seta: deslocamento perpendicular (px) do meio da curva.
     */
    public function up(): void
    {
        if (Schema::hasColumn('map_edges', 'bend')) {
            return;
        }

        Schema::table('map_edges', function (Blueprint $table) {
            $table->float('bend')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('map_edges', function (Blueprint $table) {
            $table->dropColumn('bend');
        });
    }
};
