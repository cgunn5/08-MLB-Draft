<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ncaa_player_hard_contact_visuals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('plate_heatmap_disk')->nullable();
            $table->string('plate_heatmap_path')->nullable();
            $table->string('zone_pitch_map_disk')->nullable();
            $table->string('zone_pitch_map_path')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ncaa_player_hard_contact_visuals');
    }
};
