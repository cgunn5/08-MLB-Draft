<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->string('board_type', 16)->default('hs')->after('user_id');
        });

        DB::table('working_board_entries')->update(['board_type' => 'hs']);

        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'player_id']);
            $table->dropIndex(['user_id', 'round_key', 'sort_order']);
            $table->unique(['user_id', 'board_type', 'player_id']);
            $table->index(['user_id', 'board_type', 'round_key', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'board_type', 'round_key', 'sort_order']);
            $table->dropUnique(['user_id', 'board_type', 'player_id']);
            $table->unique(['user_id', 'player_id']);
            $table->index(['user_id', 'round_key', 'sort_order']);
            $table->dropColumn('board_type');
        });
    }
};
