<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('working_board_entries')
            ->where('round_key', '4+')
            ->update(['round_key' => '4']);

        DB::table('working_board_entries')
            ->where('round_key', '10+')
            ->update(['round_key' => 'post-10']);
    }

    public function down(): void
    {
        DB::table('working_board_entries')
            ->where('round_key', '4')
            ->update(['round_key' => '4+']);

        DB::table('working_board_entries')
            ->where('round_key', 'post-10')
            ->update(['round_key' => '10+']);
    }
};
