<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notes', 'type')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->string('type', 20)->default('text');
            });
        }

        if (! Schema::hasColumn('notes', 'mind_map')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->json('mind_map')->nullable();
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
