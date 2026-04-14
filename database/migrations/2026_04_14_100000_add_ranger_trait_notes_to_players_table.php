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
        Schema::table('players', function (Blueprint $table) {
            $table->text('note_performance')->nullable()->after('grade_swing');
            $table->text('note_engine')->nullable()->after('note_performance');
            $table->text('note_approach_miss')->nullable()->after('note_engine');
            $table->text('note_left_right')->nullable()->after('note_approach_miss');
            $table->text('note_pitch_coverage')->nullable()->after('note_left_right');
            $table->text('note_swing')->nullable()->after('note_pitch_coverage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'note_performance',
                'note_engine',
                'note_approach_miss',
                'note_left_right',
                'note_pitch_coverage',
                'note_swing',
            ]);
        });
    }
};
