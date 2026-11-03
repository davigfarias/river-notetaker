<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reference_group_material')) {
            Schema::create('reference_group_material', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reference_group_id')->constrained('reference_groups', indexName: 'rgm_group_foreign')->cascadeOnDelete();
                $table->foreignId('reference_material_id')->constrained('reference_materials', indexName: 'rgm_material_foreign')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['reference_group_id', 'reference_material_id'], 'rgm_group_material_unique');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
