<?php

namespace Tests\Feature;

use App\Models\DataSourceUpload;
use App\Models\Player;
use App\Models\User;
use App\Support\NcaaDraftYearWidePerf;
use App\Support\NcaaRangerTraitsSheetResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NcaaDraftYearWidePerfTest extends TestCase
{
    use RefreshDatabase;

    public function test_ncaa_perf_maps_n_n_minus_1_n_minus_2_columns_to_three_year_rows(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $path = 'data-source-uploads/ncaa-wide-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,Draft Year,PA,PA (N-1),PA (N-2),OPS,OPS (N-1),OPS (N-2),AVG,AVG (N-1),AVG (N-2)',
            '"DOE, JANE",2026,100,200,300,.900,.800,.700,.300,.400,.500',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'NCAA overall',
            'original_filename' => 'ncaa.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => [
                'PLAYER', 'Draft Year', 'PA', 'PA (N-1)', 'PA (N-2)', 'OPS', 'OPS (N-1)', 'OPS (N-2)', 'AVG', 'AVG (N-1)', 'AVG (N-2)',
            ],
            'row_count' => 1,
            'ncaa_profile_feed_slots' => ['performance_ncaa'],
        ]);

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);
        $rows = $sheet['ncaa_perf_ncaa'];

        $this->assertCount(3, $rows);
        $this->assertSame('2026', $rows[0]['year']);
        $this->assertSame('100', $rows[0]['pa']);
        $this->assertSame('.900', $rows[0]['ops']);
        $this->assertSame('.300', $rows[0]['avg']);

        $this->assertSame('2025', $rows[1]['year']);
        $this->assertSame('200', $rows[1]['pa']);
        $this->assertSame('.800', $rows[1]['ops']);
        $this->assertSame('.400', $rows[1]['avg']);

        $this->assertSame('2024', $rows[2]['year']);
        $this->assertSame('300', $rows[2]['pa']);
        $this->assertSame('.700', $rows[2]['ops']);
        $this->assertSame('.500', $rows[2]['avg']);
    }

    public function test_ncaa_draft_year_wide_helper_detects_layout_and_maps_columns(): void
    {
        $headers = ['PLAYER', 'Draft Year', 'xwOBA', 'xwOBA (N-1)', 'SLG (N-2)'];
        $this->assertTrue(NcaaDraftYearWidePerf::usesWideLayout($headers));
        $map = NcaaDraftYearWidePerf::tierSlugColumnMap($headers, ['xwoba', 'slg']);
        $this->assertSame(2, $map[0]['xwoba']);
        $this->assertSame(3, $map[1]['xwoba']);
        $this->assertSame(4, $map[2]['slg']);
        $this->assertSame(2026, NcaaDraftYearWidePerf::parseDraftYearN('Class of 2026'));
    }

    public function test_ncaa_k_zone_wide_maps_three_year_rows(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $path = 'data-source-uploads/ncaa-kzone-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,Draft Year,K%,K% (N-1),K% (N-2),K/BB,K/BB (N-1),K/BB (N-2)',
            '"DOE, JANE",2026,20,21,22,2.5,2.6,2.7',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'NCAA overall',
            'original_filename' => 'ncaa.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => [
                'PLAYER', 'Draft Year', 'K%', 'K% (N-1)', 'K% (N-2)', 'K/BB', 'K/BB (N-1)', 'K/BB (N-2)',
            ],
            'row_count' => 1,
            'ncaa_profile_feed_slots' => ['approach_ncaa'],
        ]);

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);
        $rows = $sheet['ncaa_approach_ncaa'];

        $this->assertCount(3, $rows);
        $this->assertSame('2026', $rows[0]['year']);
        $this->assertStringContainsString('%', (string) ($rows[0]['k_pct'] ?? ''));
        $this->assertSame('2025', $rows[1]['year']);
        $this->assertSame('2024', $rows[2]['year']);
    }

    public function test_ncaa_engine_wide_maps_three_year_rows(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $path = 'data-source-uploads/ncaa-engine-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,Draft Year,EV70,EV70 (N-1),EV70 (N-2),MEV,MEV (N-1),MEV (N-2)',
            '"DOE, JANE",2026,88,87,86,105.2,104.1,103.0',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'NCAA overall',
            'original_filename' => 'ncaa.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => [
                'PLAYER', 'Draft Year', 'EV70', 'EV70 (N-1)', 'EV70 (N-2)', 'MEV', 'MEV (N-1)', 'MEV (N-2)',
            ],
            'row_count' => 1,
            'ncaa_profile_feed_slots' => ['engine_overall'],
        ]);

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);
        $rows = $sheet['ncaa_engine_ncaa'];

        $this->assertCount(3, $rows);
        $this->assertSame('2026', $rows[0]['year']);
        $this->assertSame('2025', $rows[1]['year']);
        $this->assertSame('2024', $rows[2]['year']);
    }

    public function test_ncaa_platoon_wide_maps_three_year_rows(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $path = 'data-source-uploads/ncaa-platoon-wide-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,Draft Year,OPS vs R,OPS vs R (N-1),OPS vs R (N-2),ISO vs R,ISO vs R (N-1),ISO vs R (N-2),K/BB vs R,K/BB vs R (N-1),K/BB vs R (N-2),OPS vs L,OPS vs L (N-1),OPS vs L (N-2),ISO vs L,ISO vs L (N-1),ISO vs L (N-2),K/BB vs L,K/BB vs L (N-1),K/BB vs L (N-2)',
            '"DOE, JANE",2026,.900,.800,.700,.200,.190,.180,2.5,2.4,2.3,.850,.750,.650,.150,.140,.130,2.2,2.1,2.0',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'NCAA overall',
            'original_filename' => 'ncaa.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => [
                'PLAYER', 'Draft Year', 'OPS vs R', 'OPS vs R (N-1)', 'OPS vs R (N-2)', 'ISO vs R', 'ISO vs R (N-1)', 'ISO vs R (N-2)', 'K/BB vs R', 'K/BB vs R (N-1)', 'K/BB vs R (N-2)', 'OPS vs L', 'OPS vs L (N-1)', 'OPS vs L (N-2)', 'ISO vs L', 'ISO vs L (N-1)', 'ISO vs L (N-2)', 'K/BB vs L', 'K/BB vs L (N-1)', 'K/BB vs L (N-2)',
            ],
            'row_count' => 1,
            'ncaa_profile_feed_slots' => ['platoon_ncaa'],
        ]);

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);
        $rows = $sheet['ncaa_platoon'];

        $this->assertCount(3, $rows);
        $this->assertSame('2026', $rows[0]['year']);
        $this->assertSame('.900', $rows[0]['ops_vs_r'] ?? '');
        $this->assertSame('2025', $rows[1]['year']);
        $this->assertSame('.800', $rows[1]['ops_vs_r'] ?? '');
        $this->assertSame('2024', $rows[2]['year']);
        $this->assertSame('.700', $rows[2]['ops_vs_r'] ?? '');
    }
}
