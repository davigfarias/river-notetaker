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
        Schema::table('reference_materials', function (Blueprint $table) {
            $table->text('notes_takeaway')->nullable()->after('abnt_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_materials', function (Blueprint $table) {
            $table->dropColumn('notes_takeaway');
        });
    }
};
