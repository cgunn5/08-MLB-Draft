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
            $table->string('mark_role', 64)->nullable();
            $table->string('mark_perf', 64)->nullable();
            $table->string('mark_approach', 64)->nullable();
            $table->string('mark_contact', 64)->nullable();
            $table->string('mark_damage', 64)->nullable();
            $table->string('mark_adj', 64)->nullable();
            $table->string('mark_swing', 64)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'mark_role',
                'mark_perf',
                'mark_approach',
                'mark_contact',
                'mark_damage',
                'mark_adj',
                'mark_swing',
            ]);
        });
    }
};
