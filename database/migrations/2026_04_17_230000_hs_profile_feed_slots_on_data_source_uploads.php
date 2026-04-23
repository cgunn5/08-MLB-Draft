<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->json('hs_profile_feed_slots')->nullable()->after('heat_column_stats');
        });

        if (Schema::hasColumn('data_source_uploads', 'for_hs_ranger_traits')) {
            $rows = DB::table('data_source_uploads')->select(
                'id',
                'for_hs_ranger_traits',
                'for_hs_ranger_overall'
            )->get();

            foreach ($rows as $u) {
                $traits = (bool) $u->for_hs_ranger_traits;
                $overall = (bool) $u->for_hs_ranger_overall;

                $slots = [];
                if ($traits) {
                    $slots[] = 'adjustability_pitch';
                }
                if ($overall) {
                    $slots = array_merge($slots, [
                        'performance_overall',
                        'approach_overall',
                        'impact_overall',
                        'adjustability_lr',
                    ]);
                }
                $slots = array_values(array_unique($slots));

                DB::table('data_source_uploads')->where('id', $u->id)->update([
                    'hs_profile_feed_slots' => json_encode($slots),
                ]);
            }

            Schema::table('data_source_uploads', function (Blueprint $table) {
                $table->dropColumn(['for_hs_ranger_traits', 'for_hs_ranger_overall']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->boolean('for_hs_ranger_traits')->default(false)->after('heat_column_stats');
            $table->boolean('for_hs_ranger_overall')->default(false)->after('for_hs_ranger_traits');
        });

        $rows = DB::table('data_source_uploads')->select('id', 'hs_profile_feed_slots')->get();
        foreach ($rows as $u) {
            /** @var list<string>|null $slots */
            $slots = json_decode($u->hs_profile_feed_slots ?? '[]', true);
            if (! is_array($slots)) {
                $slots = [];
            }
            $traits = in_array('adjustability_pitch', $slots, true);
            $overallSlots = [
                'performance_overall',
                'approach_overall',
                'impact_overall',
                'adjustability_lr',
            ];
            $overall = count(array_intersect($overallSlots, $slots)) > 0;
            DB::table('data_source_uploads')->where('id', $u->id)->update([
                'for_hs_ranger_traits' => $traits,
                'for_hs_ranger_overall' => $overall,
            ]);
        }

        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->dropColumn('hs_profile_feed_slots');
        });
    }
};
