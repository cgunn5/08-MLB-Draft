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
            $table->string('school')->nullable()->after('player_pool');
            $table->string('position', 32)->nullable()->after('school');
            $table->unsignedSmallInteger('aggregate_rank')->nullable()->after('position');
            $table->decimal('aggregate_score', 6, 2)->nullable()->after('aggregate_rank');
            $table->json('source_ranks')->nullable()->after('aggregate_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'school',
                'position',
                'aggregate_rank',
                'aggregate_score',
                'source_ranks',
            ]);
        });
    }
};
