<?php

use App\Models\WorkingBoardEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('working_board_entries', function (Blueprint $table): void {
            $table->string('round_key', 32)->change();
        });

        $legacyOrder = ['1', '2', '3', '4', '5-7', '8-10', 'post-10', WorkingBoardEntry::ROUND_COFFIN, '4+', '10+'];
        $groups = DB::table('working_board_entries')
            ->select('user_id', 'board_type')
            ->distinct()
            ->get();

        foreach ($groups as $group) {
            $bucketTargets = [];
            $bucketPass = [];
            foreach (WorkingBoardEntry::BOARD_BUCKET_KEYS as $bucket) {
                $bucketTargets[$bucket] = [];
                $bucketPass[$bucket] = [];
            }

            foreach ($legacyOrder as $legacyRound) {
                $entries = DB::table('working_board_entries')
                    ->where('user_id', $group->user_id)
                    ->where('board_type', $group->board_type)
                    ->where('round_key', $legacyRound)
                    ->orderBy('sort_order')
                    ->get();

                if ($entries->isEmpty()) {
                    continue;
                }

                $bucket = WorkingBoardEntry::LEGACY_ROUND_TO_BUCKET[$legacyRound] ?? null;
                if ($bucket === null) {
                    continue;
                }

                $belowPass = false;
                foreach ($entries as $entry) {
                    if ($entry->entry_type === WorkingBoardEntry::ENTRY_TYPE_NON_TARGET_DIVIDER) {
                        $belowPass = true;

                        continue;
                    }

                    $target = $belowPass ? $bucketPass[$bucket] : $bucketTargets[$bucket];
                    $target[] = $entry;
                    if ($belowPass) {
                        $bucketPass[$bucket] = $target;
                    } else {
                        $bucketTargets[$bucket] = $target;
                    }
                }
            }

            $newEntries = DB::table('working_board_entries')
                ->where('user_id', $group->user_id)
                ->where('board_type', $group->board_type)
                ->whereIn('round_key', WorkingBoardEntry::BOARD_ROUND_KEYS)
                ->orderBy('round_key')
                ->orderBy('sort_order')
                ->get();

            if ($newEntries->isNotEmpty()) {
                continue;
            }

            DB::table('working_board_entries')
                ->where('user_id', $group->user_id)
                ->where('board_type', $group->board_type)
                ->whereIn('round_key', $legacyOrder)
                ->delete();

            foreach (WorkingBoardEntry::BOARD_BUCKET_KEYS as $bucket) {
                $this->insertBucketEntries(
                    (int) $group->user_id,
                    (string) $group->board_type,
                    WorkingBoardEntry::targetsRoundKeyForBucket($bucket),
                    $bucketTargets[$bucket],
                );
                $this->insertBucketEntries(
                    (int) $group->user_id,
                    (string) $group->board_type,
                    WorkingBoardEntry::passRoundKeyForBucket($bucket),
                    $bucketPass[$bucket],
                );
            }
        }
    }

    /**
     * @param  list<object>  $entries
     */
    private function insertBucketEntries(int $userId, string $boardType, string $roundKey, array $entries): void
    {
        $sortOrder = 0;
        foreach ($entries as $entry) {
            DB::table('working_board_entries')->insert([
                'user_id' => $userId,
                'board_type' => $boardType,
                'entry_type' => $entry->entry_type,
                'player_id' => $entry->player_id,
                'round_key' => $roundKey,
                'sort_order' => $sortOrder,
                'confidence' => $entry->confidence,
                'risk' => $entry->risk,
                'created_at' => $entry->created_at,
                'updated_at' => $entry->updated_at,
            ]);
            $sortOrder++;
        }
    }

    public function down(): void
    {
        Schema::table('working_board_entries', function (Blueprint $table): void {
            $table->string('round_key', 8)->change();
        });
    }
};
