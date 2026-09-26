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
        // MySQL faz commit implícito em DDL: uma tentativa anterior falhou no
        // ALTER do índice, mas o CREATE TABLE já tinha sido gravado (tabela
        // órfã, sem registro em `migrations`). Sem dropar nada: só cria o que
        // ainda não existe.
        if (! Schema::hasTable('discipline_principle_topic')) {
            Schema::create('discipline_principle_topic', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discipline_id')->constrained('disciplines')->cascadeOnDelete();
                $table->foreignId('principle_topic_id')->constrained('principle_topics')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasIndex('discipline_principle_topic', ['discipline_id', 'principle_topic_id'], 'unique')) {
            Schema::table('discipline_principle_topic', function (Blueprint $table) {
                $table->unique(['discipline_id', 'principle_topic_id'], 'discipline_principle_topic_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipline_principle_topic');
    }
};
