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
        Schema::create('note_audios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->unique()->constrained()->cascadeOnDelete();

            /*
             * Assinatura do texto que gerou este áudio. Mudou o resumo, mudou a
             * voz ou mudou o modelo, o áudio em cache deixa de valer.
             */
            $table->string('signature', 64)->index();

            $table->string('voice');
            $table->string('mime');

            /*
             * O áudio em base64. `longText` em vez de `binary` porque
             * `$table->binary()` sem tamanho vira BLOB no MySQL, limitado a
             * 64 KB, e um áudio de resumo passa de 1 MB. `longText` funciona
             * igual em SQLite, MySQL e Postgres sem SQL cru, e a resposta do
             * SDK já chega em base64.
             */
            $table->longText('content');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_audios');
    }
};
