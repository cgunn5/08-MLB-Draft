<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['sqlite', 'mysql', 'pgsql'], true)) {
            DB::statement('UPDATE players SET player_pool = lower(trim(player_pool)) WHERE player_pool IS NOT NULL');
        }
    }

    public function down(): void
    {
        // Irreversible: prior mixed casing is not recoverable.
    }
};
