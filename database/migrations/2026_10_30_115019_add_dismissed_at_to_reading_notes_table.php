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
        Schema::table('reading_notes', function (Blueprint $table) {
            $table->timestamp('dismissed_at')->nullable()->after('consolidated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reading_notes', function (Blueprint $table) {
            $table->dropColumn('dismissed_at');
        });
    }
};
