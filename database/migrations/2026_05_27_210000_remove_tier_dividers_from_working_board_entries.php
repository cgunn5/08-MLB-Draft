<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('working_board_entries', 'entry_type')) {
            return;
        }

        DB::table('working_board_entries')->where('entry_type', 'tier_divider')->delete();

        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
        });

        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->dropColumn('entry_type');
            $table->unsignedBigInteger('player_id')->nullable(false)->change();
        });

        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
        });

        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->string('entry_type', 32)->default('player')->after('board_type');
            $table->unsignedBigInteger('player_id')->nullable()->change();
        });

        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
        });
    }
};
