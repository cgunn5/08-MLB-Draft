<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AggregatesPlayerSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/aggregates_players.json');

        if (! File::isFile($path)) {
            $this->command?->warn('Skipping aggregates import: missing database/data/aggregates_players.json');

            return;
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            Player::query()->updateOrCreate(
                [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'player_pool' => $row['player_pool'],
                ],
                [
                    'school' => $row['school'] ?? null,
                    'position' => $row['position'] ?? null,
                    'aggregate_rank' => $row['aggregate_rank'] ?? null,
                    'aggregate_score' => $row['aggregate_score'] ?? null,
                    'source_ranks' => $row['source_ranks'] ?? null,
                ],
            );
        }
    }
}
