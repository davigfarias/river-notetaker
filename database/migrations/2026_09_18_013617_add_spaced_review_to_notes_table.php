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
        Schema::table('notes', function (Blueprint $table) {
            $table->unsignedTinyInteger('review_stage')->default(0)->after('ai_summary');
            $table->date('next_review_at')->nullable()->after('review_stage');
            $table->timestamp('consolidated_at')->nullable()->after('next_review_at');

            $table->index(['next_review_at', 'discipline_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex(['next_review_at', 'discipline_id']);
            $table->dropColumn(['review_stage', 'next_review_at', 'consolidated_at']);
        });
    }
};
