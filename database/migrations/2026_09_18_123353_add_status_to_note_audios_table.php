<?php

use App\Enums\SummaryAudioStatus;
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
        Schema::table('note_audios', function (Blueprint $table) {
            /*
             * O registro passa a existir desde o pedido, não só depois do
             * sucesso: assim a tela sabe na hora se a locução está sendo
             * gerada ou se falhou, em vez de esperar o prazo inteiro.
             */
            $table->string('status')->default(SummaryAudioStatus::Ready->value)->after('note_id');
            $table->string('failure_reason')->nullable()->after('mime');
        });

        Schema::table('note_audios', function (Blueprint $table) {
            $table->longText('content')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('note_audios', function (Blueprint $table) {
            $table->dropColumn(['status', 'failure_reason']);
        });
    }
};
