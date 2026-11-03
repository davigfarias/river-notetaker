<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reference_map_nodes')) {
            Schema::create('reference_map_nodes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reference_material_id')->constrained('reference_materials', indexName: 'rmn_material_foreign')->cascadeOnDelete();
                $table->string('key', 120);
                $table->string('label');
                $table->float('x')->default(0);
                $table->float('y')->default(0);
                $table->boolean('custom')->default(false);
                $table->timestamps();

                $table->unique(['reference_material_id', 'key'], 'rmn_material_key_unique');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
