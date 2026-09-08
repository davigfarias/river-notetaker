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
            $table->string('book_format')->nullable()->after('type');
            $table->unsignedInteger('page_count')->nullable()->after('book_format');
            $table->unsignedInteger('current_page')->nullable()->after('page_count');
            $table->unsignedInteger('reader_start_page')->nullable()->after('current_page');
            $table->unsignedInteger('reader_end_page')->nullable()->after('reader_start_page');
            $table->string('reading_status')->nullable()->after('reader_end_page');
            $table->date('reading_started_at')->nullable()->after('reading_status');
            $table->date('reading_finished_at')->nullable()->after('reading_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_materials', function (Blueprint $table) {
            $table->dropColumn([
                'book_format',
                'page_count',
                'current_page',
                'reader_start_page',
                'reader_end_page',
                'reading_status',
                'reading_started_at',
                'reading_finished_at',
            ]);
        });
    }
};
