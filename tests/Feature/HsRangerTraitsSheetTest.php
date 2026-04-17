<?php

namespace Tests\Feature;

use App\Models\DataSourceUpload;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HsRangerTraitsSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_hs_player_page_shows_values_from_flagged_data_source(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $path = 'data-source-uploads/hs-sheet-'.uniqid('', true).'.csv';
        $uniqueG = '76234';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,YEAR,G,PA,AVG,OBP,SLG,OPS',
            '"DOE, JANE",2024,'.$uniqueG.',88,.350,.400,.500,.900',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'HS metrics',
            'original_filename' => 'hs.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'YEAR', 'G', 'PA', 'AVG', 'OBP', 'SLG', 'OPS'],
            'row_count' => 1,
            'for_hs_ranger_traits' => true,
        ]);

        $response = $this->actingAs($user)->get(route('hs.players.show', $player));

        $response->assertOk();
        $response->assertSee($uniqueG, false);
    }

    public function test_only_one_upload_may_be_hs_ranger_source_per_user(): void
    {
        $user = User::factory()->create();
        $pathA = 'data-source-uploads/hs-a-'.uniqid('', true).'.csv';
        $pathB = 'data-source-uploads/hs-b-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathA, "PLAYER\nX\n");
        Storage::disk('local')->put($pathB, "PLAYER\nY\n");

        $a = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'A',
            'original_filename' => 'a.csv',
            'disk' => 'local',
            'path' => $pathA,
            'header_row' => ['PLAYER'],
            'row_count' => 1,
            'for_hs_ranger_traits' => true,
        ]);
        $b = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'B',
            'original_filename' => 'b.csv',
            'disk' => 'local',
            'path' => $pathB,
            'header_row' => ['PLAYER'],
            'row_count' => 1,
            'for_hs_ranger_traits' => false,
        ]);

        $this->actingAs($user)->patchJson(route('data-sources.uploads.settings', $b), [
            'for_hs_ranger_traits' => true,
        ])->assertOk();

        $this->assertFalse($a->fresh()->for_hs_ranger_traits);
        $this->assertTrue($b->fresh()->for_hs_ranger_traits);
    }
}
