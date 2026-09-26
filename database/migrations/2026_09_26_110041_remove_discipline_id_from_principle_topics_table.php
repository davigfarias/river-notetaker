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
        Schema::table('principle_topics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discipline_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('principle_topics', function (Blueprint $table) {
            $table->foreignId('discipline_id')->nullable()->after('slug')->constrained('disciplines')->nullOnDelete();
        });
    }
};
