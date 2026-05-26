<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql' && $driver !== 'pgsql' && $driver !== 'sqlite') {
            return;
        }

        $rows = DB::table('data_source_uploads')
            ->whereNotNull('ncaa_profile_feed_slots')
            ->get(['id', 'ncaa_profile_feed_slots']);

        foreach ($rows as $row) {
            $slots = json_decode($row->ncaa_profile_feed_slots ?? 'null', true);
            if (! is_array($slots) || $slots === []) {
                continue;
            }
            $changed = false;
            $next = [];
            foreach ($slots as $s) {
                if ($s === 'left_right_overall') {
                    $next[] = 'platoon_ncaa';
                    $changed = true;
                } else {
                    $next[] = $s;
                }
            }
            if (! $changed) {
                continue;
            }
            $next = array_values(array_unique($next));
            DB::table('data_source_uploads')->where('id', $row->id)->update([
                'ncaa_profile_feed_slots' => json_encode($next),
            ]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('data_source_uploads')
            ->whereNotNull('ncaa_profile_feed_slots')
            ->get(['id', 'ncaa_profile_feed_slots']);

        foreach ($rows as $row) {
            $slots = json_decode($row->ncaa_profile_feed_slots ?? 'null', true);
            if (! is_array($slots) || $slots === []) {
                continue;
            }
            $changed = false;
            $next = [];
            foreach ($slots as $s) {
                if ($s === 'platoon_ncaa') {
                    $next[] = 'left_right_overall';
                    $changed = true;
                } else {
                    $next[] = $s;
                }
            }
            if (! $changed) {
                continue;
            }
            $next = array_values(array_unique($next));
            DB::table('data_source_uploads')->where('id', $row->id)->update([
                'ncaa_profile_feed_slots' => json_encode($next),
            ]);
        }
    }
};
