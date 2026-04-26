<?php

namespace Tests\Unit;

use App\Models\DataSourceUpload;
use App\Models\Player;
use App\Models\User;
use App\Support\HsRangerTraitsSheetResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HsOverallDemographicsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function performance_overall_row_populates_overall_demographics(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $path = 'data-source-uploads/hs-demo-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,YEAR,G,PA,AVG,OBP,SLG,OPS,BATS,THROWS,AGE',
            '"DOE, JANE",2024,10,88,.350,.400,.500,.900,L,R,17.25',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'HS Overall',
            'original_filename' => 'hs.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'YEAR', 'G', 'PA', 'AVG', 'OBP', 'SLG', 'OPS', 'BATS', 'THROWS', 'AGE'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['performance_overall'],
        ]);

        $sheet = app(HsRangerTraitsSheetResolver::class)->resolve($player, $user, null);

        $this->assertIsArray($sheet['overall_demographics'] ?? null);
        $this->assertSame('L', $sheet['overall_demographics']['bats']);
        $this->assertSame('R', $sheet['overall_demographics']['throws']);
        $this->assertSame('17.25', $sheet['overall_demographics']['age']);
    }

    #[Test]
    public function performance_overall_maps_single_letter_b_and_t_headers(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $path = 'data-source-uploads/hs-demo-bt-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,YEAR,G,PA,AVG,OBP,SLG,OPS,B,T,AGE',
            '"DOE, JANE",2024,10,88,.350,.400,.500,.900,L,R,17.25',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'HS Overall',
            'original_filename' => 'hs.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'YEAR', 'G', 'PA', 'AVG', 'OBP', 'SLG', 'OPS', 'B', 'T', 'AGE'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['performance_overall'],
        ]);

        $sheet = app(HsRangerTraitsSheetResolver::class)->resolve($player, $user, null);

        $this->assertIsArray($sheet['overall_demographics'] ?? null);
        $this->assertSame('L', $sheet['overall_demographics']['bats']);
        $this->assertSame('R', $sheet['overall_demographics']['throws']);
    }
}
