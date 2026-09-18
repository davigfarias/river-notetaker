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
        Schema::table('disciplines', function (Blueprint $table) {
            $table->unsignedTinyInteger('period')->nullable()->after('title');
            $table->string('code')->nullable()->after('period');
            $table->string('professor')->nullable()->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disciplines', function (Blueprint $table) {
            $table->dropColumn(['period', 'code', 'professor']);
        });
    }
};
