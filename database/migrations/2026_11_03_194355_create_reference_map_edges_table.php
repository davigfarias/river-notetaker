<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reference_map_edges')) {
            Schema::create('reference_map_edges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reference_material_id')->constrained('reference_materials', indexName: 'rme_material_foreign')->cascadeOnDelete();
                $table->string('from_key', 120);
                $table->string('to_key', 120);
                $table->float('bend')->default(0);
                $table->timestamps();

                $table->unique(['reference_material_id', 'from_key', 'to_key'], 'rme_material_from_to_unique');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
