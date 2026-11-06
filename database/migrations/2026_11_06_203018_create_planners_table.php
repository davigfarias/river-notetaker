<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_token_id')->nullable()->constrained('access_tokens')->nullOnDelete();
            $table->string('name');
            $table->text('objective')->nullable();
            $table->date('target_date')->nullable();
            $table->timestamps();

            $table->index('access_token_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planners');
    }
};
