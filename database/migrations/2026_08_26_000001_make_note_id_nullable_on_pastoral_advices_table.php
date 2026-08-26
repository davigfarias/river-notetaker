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
        Schema::table('pastoral_advices', function (Blueprint $table) {
            $table->dropForeign(['note_id']);

            $table->unsignedBigInteger('note_id')->nullable()->change();

            $table->foreign('note_id')->references('id')->on('notes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pastoral_advices', function (Blueprint $table) {
            $table->dropForeign(['note_id']);

            $table->unsignedBigInteger('note_id')->nullable(false)->change();

            $table->foreign('note_id')->references('id')->on('notes');
        });
    }
};
