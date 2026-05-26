<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_board_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('round_key', 8);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('confidence', 32)->nullable();
            $table->string('risk', 32)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'player_id']);
            $table->index(['user_id', 'round_key', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_board_entries');
    }
};
