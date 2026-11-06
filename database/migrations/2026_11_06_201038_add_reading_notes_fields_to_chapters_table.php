<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O capítulo passa a guardar a anotação do livro (texto, local, tags) e a
 * escada de revisão espaçada. `legacy_reading_note_id` marca de qual
 * anotação antiga o capítulo veio, para a cópia poder rodar de novo sem
 * duplicar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->text('body')->nullable()->after('title');
            $table->string('location')->nullable()->after('body');
            $table->text('tags')->nullable()->after('location');
            $table->unsignedInteger('page_snapshot')->nullable()->after('tags');
            $table->unsignedTinyInteger('review_stage')->default(0)->after('page_snapshot');
            $table->date('next_review_at')->nullable()->after('review_stage');
            $table->timestamp('consolidated_at')->nullable()->after('next_review_at');
            $table->timestamp('dismissed_at')->nullable()->after('consolidated_at');
            $table->unsignedBigInteger('legacy_reading_note_id')->nullable()->unique()->after('dismissed_at');

            $table->index(['next_review_at', 'reference_material_id'], 'chapters_review_due_index');
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropIndex('chapters_review_due_index');
            $table->dropUnique(['legacy_reading_note_id']);
            $table->dropColumn([
                'body', 'location', 'tags', 'page_snapshot', 'review_stage',
                'next_review_at', 'consolidated_at', 'dismissed_at', 'legacy_reading_note_id',
            ]);
        });
    }
};
