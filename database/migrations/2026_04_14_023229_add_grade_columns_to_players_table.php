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
            $table->string('grade_role', 64)->nullable();
            $table->string('grade_perf', 64)->nullable();
            $table->string('grade_approach', 64)->nullable();
            $table->string('grade_contact', 64)->nullable();
            $table->string('grade_damage', 64)->nullable();
            $table->string('grade_adj', 64)->nullable();
            $table->string('grade_swing', 64)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'grade_role',
                'grade_perf',
                'grade_approach',
                'grade_contact',
                'grade_damage',
                'grade_adj',
                'grade_swing',
            ]);
        });
    }
};
