<?php

namespace Tests\Feature;

use App\Models\DataSourceUpload;
use App\Models\Player;
use App\Models\User;
use App\Support\HsRangerTraitsSheetResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HsRangerTraitsSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_hs_player_page_shows_values_from_assigned_data_source(): void
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
            'hs_profile_feed_slots' => ['performance_overall'],
        ]);

        $response = $this->actingAs($user)->get(route('hs.players.show', $player));

        $response->assertOk();
        $response->assertSee($uniqueG, false);
    }

    public function test_hs_profile_slot_is_exclusive_per_user(): void
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
            'hs_profile_feed_slots' => ['adjustability_pitch'],
        ]);
        $b = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'B',
            'original_filename' => 'b.csv',
            'disk' => 'local',
            'path' => $pathB,
            'header_row' => ['PLAYER'],
            'row_count' => 1,
            'hs_profile_feed_slots' => [],
        ]);

        $this->actingAs($user)->patchJson(route('data-sources.uploads.settings', $b), [
            'hs_profile_feed_slots' => ['adjustability_pitch'],
        ])->assertOk();

        $this->assertSame([], $a->fresh()->hs_profile_feed_slots ?? []);
        $this->assertSame(['adjustability_pitch'], $b->fresh()->hs_profile_feed_slots ?? []);
    }

    public function test_hs_settings_patch_returns_hs_profile_feed_assignments_for_all_datasets(): void
    {
        $user = User::factory()->create();
        $pathA = 'data-source-uploads/hs-sum-a-'.uniqid('', true).'.csv';
        $pathB = 'data-source-uploads/hs-sum-b-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathA, "PLAYER\nA\n");
        Storage::disk('local')->put($pathB, "PLAYER\nB\n");

        $a = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'One',
            'original_filename' => '1.csv',
            'disk' => 'local',
            'path' => $pathA,
            'header_row' => ['PLAYER'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['performance_overall'],
        ]);
        $b = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Two',
            'original_filename' => '2.csv',
            'disk' => 'local',
            'path' => $pathB,
            'header_row' => ['PLAYER'],
            'row_count' => 1,
            'hs_profile_feed_slots' => [],
        ]);

        $response = $this->actingAs($user)->patchJson(route('data-sources.uploads.settings', $b), [
            'hs_profile_feed_slots' => ['adjustability_pitch'],
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['hs_profile_feed_assignments']);
        $payload = $response->json('hs_profile_feed_assignments');
        $this->assertCount(2, $payload);
        $byId = collect($payload)->keyBy('id');
        $this->assertSame(['performance_overall'], $byId[$a->id]['hs_profile_feed_slots']);
        $this->assertSame(['adjustability_pitch'], $byId[$b->id]['hs_profile_feed_slots']);
    }

    public function test_hs_adjustability_uses_pitch_rows_and_overall_row_for_other_blocks(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
        ]);

        $path = 'data-source-uploads/hs-pitch-split-'.uniqid('', true).'.csv';
        $headers = [
            'PLAYER', 'YEAR', 'TYPE', 'G', 'PA', 'AVG', 'OBP', 'SLG', 'OPS', 'BB%', 'K%', 'SW%', 'SWDEC', 'CH%', 'PPA', 'SWM%', 'IZ SWM%', 'ISO', 'EV70', 'EV95', 'MAX EV', 'BIP 100+', 'BIP 105+', 'NITRO%', 'TX BAL%', 'GB%', 'FB%', 'LD%', 'PA VS R', 'OPS VS R', 'PA VS L', 'OPS VS L', 'P', 'BIPx', 'ISO', 'EV95', 'GB%', 'SWM%', 'IZSWM%', 'CH%',
        ];
        $n = count($headers);
        $overall = [
            'RIVERA, ALEX', '2024', '', '20', '100', '.300', '.400', '.500', '.900', '10', '20', '45', '12', '30', '4.1', '8', '9', '.200', '90', '95', '100', '5', '2', '10', '5', '40', '30', '30', '50', '.800', '40', '.750', '100', '1.2', '50', '15', '92', '35', '12', '8',
        ];
        $this->assertCount($n, $overall);
        $pitchTailFb = ['1200', '.310', '.080', '94', '38', '11', '7', '22'];
        $pitchTailBb = ['400', '.290', '.050', '88', '42', '15', '10', '28'];
        $pitchTailOs = ['300', '.270', '.040', '85', '45', '18', '12', '30'];
        $gapMiddle = 29;
        $rowFb = array_merge(['', '2024', 'FB'], array_fill(0, $gapMiddle, ''), $pitchTailFb);
        $rowBb = array_merge(['', '2024', 'BB'], array_fill(0, $gapMiddle, ''), $pitchTailBb);
        $rowOs = array_merge(['', '2024', 'OS'], array_fill(0, $gapMiddle, ''), $pitchTailOs);
        $this->assertCount($n, $rowFb);
        $fullPath = Storage::disk('local')->path($path);
        $fh = fopen($fullPath, 'w');
        $this->assertNotFalse($fh);
        try {
            fputcsv($fh, $headers);
            fputcsv($fh, $overall);
            fputcsv($fh, $rowFb);
            fputcsv($fh, $rowBb);
            fputcsv($fh, $rowOs);
        } finally {
            fclose($fh);
        }
        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'HS pitch types',
            'original_filename' => 'hs-pitch.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => $headers,
            'row_count' => 4,
            'hs_profile_feed_slots' => [
                'performance_overall',
                'approach_overall',
                'impact_overall',
                'adjustability_lr',
                'adjustability_pitch',
            ],
        ]);

        $sheet = app(HsRangerTraitsSheetResolver::class)->resolve($player, $user);

        $this->assertSame('20', $sheet['circuit_lonestar']['g'] ?? null);
        $this->assertSame('100', $sheet['circuit_lonestar']['pa'] ?? null);
        $this->assertSame('10', $sheet['approach_lonestar']['bb_pct'] ?? null);

        $adj = $sheet['adjust_pitch'];
        $this->assertCount(3, $adj);
        $this->assertSame('FB', $adj[0]['pitch'] ?? null);
        $this->assertSame('1200', $adj[0]['p'] ?? null);
        $this->assertSame('.310', $adj[0]['bipx'] ?? null);
        $this->assertSame('BB', $adj[1]['pitch'] ?? null);
        $this->assertSame('400', $adj[1]['p'] ?? null);
        $this->assertSame('OS', $adj[2]['pitch'] ?? null);
        $this->assertSame('300', $adj[2]['p'] ?? null);
    }

    public function test_adjust_pitch_heat_gates_on_p_when_pa_column_absent(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
        ]);

        $path = 'data-source-uploads/hs-pitch-p-heat-'.uniqid('', true).'.csv';
        $headers = ['PLAYER', 'YEAR', 'TYPE', 'P', 'BIPx', 'OPS', 'ISO', 'EV95', 'GB%', 'SWM%', 'IZ SWM%', 'CH%'];
        $tail = ['.310', '.900', '.080', '94', '38', '11', '7', '22'];
        $rowFb = array_merge(['RIVERA, ALEX', '2024', 'FB', '2000'], $tail);
        $rowBb = ['RIVERA, ALEX', '2024', 'BB', '50', '.290', '.700', '.050', '88', '42', '15', '10', '28'];
        $rowOs = array_merge(['RIVERA, ALEX', '2024', 'OS', '1200'], ['.270', '.650', '.040', '85', '45', '18', '12', '30']);
        $rowFbLow = array_merge(['LOW, OTHER', '2024', 'FB', '2000'], ['.300', '.500', '.050', '90', '40', '10', '8', '20']);
        $rowFbHigh = array_merge(['HIGH, OTHER', '2024', 'FB', '2000'], ['.300', '1.100', '.200', '95', '35', '9', '6', '18']);
        $rowOsA = array_merge(['AAA, OTHER', '2024', 'OS', '1500'], ['.270', '.500', '.030', '82', '46', '19', '13', '31']);
        $rowOsB = array_merge(['BBB, OTHER', '2024', 'OS', '1600'], ['.270', '.800', '.050', '88', '44', '17', '11', '29']);
        $rowBbBig = array_merge(['BIG, OTHER', '2024', 'BB', '2000'], ['.290', '.600', '.050', '88', '42', '15', '10', '28']);
        $this->assertCount(count($headers), $rowFb);

        $fullPath = Storage::disk('local')->path($path);
        $fh = fopen($fullPath, 'w');
        $this->assertNotFalse($fh);
        try {
            fputcsv($fh, $headers);
            fputcsv($fh, $rowFb);
            fputcsv($fh, $rowBb);
            fputcsv($fh, $rowOs);
            fputcsv($fh, $rowFbLow);
            fputcsv($fh, $rowFbHigh);
            fputcsv($fh, $rowOsA);
            fputcsv($fh, $rowOsB);
            fputcsv($fh, $rowBbBig);
        } finally {
            fclose($fh);
        }

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'HS pitch only',
            'original_filename' => 'hs-pitch-p.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => $headers,
            'row_count' => 8,
            'hs_profile_feed_slots' => ['adjustability_pitch'],
            'dataset_browse_settings' => ['heat_min_pa' => 1000],
            'heat_rules' => [
                'OPS' => ['enabled' => true, 'higher_is_better' => true],
            ],
        ]);

        $sheet = app(HsRangerTraitsSheetResolver::class)->resolve($player, $user);
        $heat = $sheet['cell_heat']['adjust_pitch'] ?? [];

        $this->assertIsArray($heat);
        $this->assertCount(3, $heat);
        $this->assertArrayHasKey('ops', $heat[0]);
        $this->assertArrayNotHasKey('ops', $heat[1]);
        $this->assertArrayHasKey('ops', $heat[2]);
    }

    public function test_adjust_pitch_heat_uses_pitch_p_when_pa_lower_than_cutoff(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
        ]);

        $path = 'data-source-uploads/hs-pitch-p-over-pa-'.uniqid('', true).'.csv';
        $headers = ['PLAYER', 'YEAR', 'TYPE', 'PA', 'P', 'BIPx', 'OPS', 'ISO', 'EV95', 'GB%', 'SWM%', 'IZ SWM%', 'CH%'];
        $tail = ['.310', '.900', '.080', '94', '38', '11', '7', '22'];
        $rowFb = array_merge(['RIVERA, ALEX', '2024', 'FB', '30', '200'], $tail);
        $rowBb = array_merge(['RIVERA, ALEX', '2024', 'BB', '30', '50'], $tail);
        $rowOs = array_merge(['RIVERA, ALEX', '2024', 'OS', '30', '1200'], ['.270', '.650', '.040', '85', '45', '18', '12', '30']);
        $rowFbLow = array_merge(['LOW, OTHER', '2024', 'FB', '100', '2000'], ['.300', '.500', '.050', '90', '40', '10', '8', '20']);
        $rowFbHigh = array_merge(['HIGH, OTHER', '2024', 'FB', '100', '2000'], ['.300', '1.100', '.200', '95', '35', '9', '6', '18']);
        $rowOsA = array_merge(['AAA, OTHER', '2024', 'OS', '100', '1500'], ['.270', '.500', '.030', '82', '46', '19', '13', '31']);
        $rowOsB = array_merge(['BBB, OTHER', '2024', 'OS', '100', '1600'], ['.270', '.800', '.050', '88', '44', '17', '11', '29']);
        $rowBbBig = array_merge(['BIG, OTHER', '2024', 'BB', '100', '2000'], $tail);

        $fullPath = Storage::disk('local')->path($path);
        $fh = fopen($fullPath, 'w');
        $this->assertNotFalse($fh);
        try {
            fputcsv($fh, $headers);
            fputcsv($fh, $rowFb);
            fputcsv($fh, $rowBb);
            fputcsv($fh, $rowOs);
            fputcsv($fh, $rowFbLow);
            fputcsv($fh, $rowFbHigh);
            fputcsv($fh, $rowOsA);
            fputcsv($fh, $rowOsB);
            fputcsv($fh, $rowBbBig);
        } finally {
            fclose($fh);
        }

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'HS pitch P vs PA',
            'original_filename' => 'x.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => $headers,
            'row_count' => 8,
            'hs_profile_feed_slots' => ['adjustability_pitch'],
            'dataset_browse_settings' => ['heat_min_pa' => 80],
            'heat_rules' => [
                'OPS' => ['enabled' => true, 'higher_is_better' => true],
            ],
        ]);

        $sheet = app(HsRangerTraitsSheetResolver::class)->resolve($player, $user);
        $heat = $sheet['cell_heat']['adjust_pitch'] ?? [];
        $this->assertArrayHasKey('ops', $heat[0]);
        $this->assertArrayNotHasKey('ops', $heat[1]);
        $this->assertArrayHasKey('ops', $heat[2]);
    }

    public function test_performance_overall_slot_is_exclusive_per_user(): void
    {
        $user = User::factory()->create();
        $pathA = 'data-source-uploads/hs-oa-'.uniqid('', true).'.csv';
        $pathB = 'data-source-uploads/hs-ob-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathA, "PLAYER\nX\n");
        Storage::disk('local')->put($pathB, "PLAYER\nY\n");

        $a = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'OA',
            'original_filename' => 'a.csv',
            'disk' => 'local',
            'path' => $pathA,
            'header_row' => ['PLAYER'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['performance_overall'],
        ]);
        $b = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'OB',
            'original_filename' => 'b.csv',
            'disk' => 'local',
            'path' => $pathB,
            'header_row' => ['PLAYER'],
            'row_count' => 1,
            'hs_profile_feed_slots' => [],
        ]);

        $this->actingAs($user)->patchJson(route('data-sources.uploads.settings', $b), [
            'hs_profile_feed_slots' => ['performance_overall'],
        ])->assertOk();

        $this->assertSame([], $a->fresh()->hs_profile_feed_slots ?? []);
        $this->assertSame(['performance_overall'], $b->fresh()->hs_profile_feed_slots ?? []);
    }

    public function test_hs_profile_merges_separate_overall_and_pitch_uploads(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Casey',
            'last_name' => 'Merge',
        ]);

        $pathOverall = 'data-source-uploads/hs-overall-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathOverall, implode("\n", [
            'PLAYER,YEAR,G,PA,AVG,OBP,SLG,OPS',
            '"MERGE, CASEY",2024,77,50,.250,.330,.410,.740',
        ]));

        $pathPitch = 'data-source-uploads/hs-pitchonly-'.uniqid('', true).'.csv';
        $ph = ['PLAYER', 'YEAR', 'TYPE', 'G', 'PA', 'AVG', 'OBP', 'SLG', 'OPS', 'BB%', 'K%', 'SW%', 'SWDEC', 'CH%', 'PPA', 'SWM%', 'IZ SWM%', 'ISO', 'EV70', 'EV95', 'MAX EV', 'BIP 100+', 'BIP 105+', 'NITRO%', 'TX BAL%', 'GB%', 'FB%', 'LD%', 'PA VS R', 'OPS VS R', 'PA VS L', 'OPS VS L', 'P', 'BIPx', 'ISO', 'EV95', 'GB%', 'SWM%', 'IZSWM%', 'CH%'];
        $n = count($ph);
        $gap = 29;
        $tail = ['5', '.200', '.050', '80', '30', '5', '4', '10'];
        $rowFb = array_merge(['MERGE, CASEY', '2024', 'FB'], array_fill(0, $gap, ''), $tail);
        $this->assertCount($n, $rowFb);
        $fullPath = Storage::disk('local')->path($pathPitch);
        $fh = fopen($fullPath, 'w');
        $this->assertNotFalse($fh);
        try {
            fputcsv($fh, $ph);
            fputcsv($fh, $rowFb);
        } finally {
            fclose($fh);
        }

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Overall stats',
            'original_filename' => 'o.csv',
            'disk' => 'local',
            'path' => $pathOverall,
            'header_row' => ['PLAYER', 'YEAR', 'G', 'PA', 'AVG', 'OBP', 'SLG', 'OPS'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['performance_overall'],
        ]);
        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Pitch types',
            'original_filename' => 'p.csv',
            'disk' => 'local',
            'path' => $pathPitch,
            'header_row' => $ph,
            'row_count' => 1,
            'hs_profile_feed_slots' => ['adjustability_pitch'],
        ]);

        $sheet = app(HsRangerTraitsSheetResolver::class)->resolve($player, $user);

        $this->assertSame('77', $sheet['circuit_lonestar']['g'] ?? null);
        $this->assertSame('5', $sheet['adjust_pitch'][0]['p'] ?? null);
        $this->assertSame('FB', $sheet['adjust_pitch'][0]['pitch'] ?? null);
    }

    public function test_hs_pg_chart_prepends_career_row_with_same_shape_as_season_rows(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $path = 'data-source-uploads/hs-pg-career-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,YEAR,PA,1B,2B,3B,HR,BB,K,OPS,AVG,OBP,SLG,ISO,BB%,K%',
            '"DOE, JANE",2024,30,15,0,0,0,6,9,.900,.500,.700,.500,.000,0.200,0.300',
            '"DOE, JANE",2023,20,10,0,0,0,4,5,.800,.500,.700,.500,.000,0.200,0.250',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'PG stats',
            'original_filename' => 'pg.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'YEAR', 'PA', '1B', '2B', '3B', 'HR', 'BB', 'K', 'OPS', 'AVG', 'OBP', 'SLG', 'ISO', 'BB%', 'K%'],
            'row_count' => 2,
            'hs_profile_feed_slots' => ['performance_pg'],
        ]);

        $sheet = app(HsRangerTraitsSheetResolver::class)->resolve($player, $user);

        $cp = $sheet['circuit_pg'] ?? [];
        $this->assertCount(3, $cp);
        $this->assertSame('Career', $cp[0]['year'] ?? null);
        $this->assertSame('50', $cp[0]['pa'] ?? null);
        $this->assertSame('20.0%', $cp[0]['bb_pct'] ?? null);
        $this->assertSame('28.0%', $cp[0]['k_pct'] ?? null);
        $this->assertSame('2024', $cp[1]['year'] ?? null);
        $this->assertSame('2023', $cp[2]['year'] ?? null);
    }

    public function test_circuit_pg_maps_k_pct_column_not_strikeout_count_k(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Sam',
            'last_name' => 'Case',
        ]);

        $path = 'data-source-uploads/hs-pg-k-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,YEAR,PA,K,K%,BB%,1B,2B,3B,HR,OPS,AVG,OBP,SLG,ISO',
            '"CASE, SAM",2024,100,50,9.5,12.0,10,0,0,0,.800,.100,.200,.300,.200',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'PG',
            'original_filename' => 'pg.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => explode(',', 'PLAYER,YEAR,PA,K,K%,BB%,1B,2B,3B,HR,OPS,AVG,OBP,SLG,ISO'),
            'row_count' => 1,
            'hs_profile_feed_slots' => ['performance_pg'],
        ]);

        $sheet = app(HsRangerTraitsSheetResolver::class)->resolve($player, $user);
        $cp = $sheet['circuit_pg'] ?? [];
        $yearRow = collect($cp)->firstWhere('year', '2024');
        $this->assertNotNull($yearRow);
        $this->assertSame('9.5%', $yearRow['k_pct'] ?? null);
    }

    public function test_circuit_pg_k_pct_resolves_when_percent_column_is_before_strikeout_count(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Will',
            'last_name' => 'Brick',
        ]);

        $path = 'data-source-uploads/hs-pg-k-order-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,YEAR,PA,K%,K,BB%,1B,2B,3B,HR,OPS,AVG,OBP,SLG,ISO',
            '"BRICK, WILL",2024,100,9.5,50,12.0,10,0,0,0,.800,.100,.200,.300,.200',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'PG',
            'original_filename' => 'pg.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => explode(',', 'PLAYER,YEAR,PA,K%,K,BB%,1B,2B,3B,HR,OPS,AVG,OBP,SLG,ISO'),
            'row_count' => 1,
            'hs_profile_feed_slots' => ['performance_pg'],
        ]);

        $sheet = app(HsRangerTraitsSheetResolver::class)->resolve($player, $user);
        $yearRow = collect($sheet['circuit_pg'] ?? [])->firstWhere('year', '2024');
        $this->assertNotNull($yearRow);
        $this->assertSame('9.5%', $yearRow['k_pct'] ?? null);
    }

    public function test_hs_comp_heat_scope_changes_performance_overall_ops_heat(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $path = 'data-source-uploads/hs-comp-heat-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,YEAR,Rnds,G,PA,AVG,OBP,SLG,OPS',
            '"DOE, JANE",2024,1-2,1,100,.350,.400,.500,.900',
            '"OTHER, A",2024,7+,1,100,.200,.250,.300,.500',
            '"OTHER, B",2024,1-2,1,100,.400,.450,.600,1.100',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'HS Stats Overall',
            'original_filename' => 'hs.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'YEAR', 'Rnds', 'G', 'PA', 'AVG', 'OBP', 'SLG', 'OPS'],
            'row_count' => 3,
            'hs_profile_feed_slots' => ['performance_overall'],
            'heat_rules' => [
                'OPS' => ['enabled' => true, 'higher_is_better' => true],
            ],
            'heat_column_stats' => [
                'OPS' => ['min' => 0.5, 'max' => 1.1, 'median' => 0.9],
            ],
        ]);

        $resolver = app(HsRangerTraitsSheetResolver::class);
        $overall = $resolver->resolve($player, $user, null);
        $scoped = $resolver->resolve($player, $user, '1-2');

        $h0 = $overall['cell_heat']['circuit_lonestar']['ops'] ?? null;
        $h1 = $scoped['cell_heat']['circuit_lonestar']['ops'] ?? null;
        $this->assertNotNull($h0);
        $this->assertNotNull($h1);
        $this->assertNotSame($h0, $h1);
    }

    public function test_hs_radar_quintiles_use_overall_columns_and_comp_scope(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $hdr = 'PLAYER,YEAR,Rnds,PA,G,AVG,OBP,SLG,OPS,BB%,K%,SW%,SWDEC,CH%,PPA,SWM%,IZ SWM%,ISO,EV70,EV95,MAX EV,BIP 100+,BIP 105+,NITRO%,TX BAL%,GB%,FB%,LD%';
        $path = 'data-source-uploads/hs-radar-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            $hdr,
            '"DOE, JANE",2024,1-2,100,1,.300,.400,.500,.900,10,20,45,50,25,4,12,10,.200,80,95,100,1,0,0,0,40,30,30',
            '"LOW, OPS",2024,1-2,100,1,.200,.250,.300,.500,10,20,45,50,30,4,8,8,.100,70,85,90,1,0,0,0,50,25,25',
            '"HIGH, OPS",2024,1-2,100,1,.400,.450,.600,1.100,10,20,45,50,20,4,15,12,.250,85,100,105,1,0,0,0,35,35,30',
            '"OTHER, X",2024,7+,100,1,.250,.330,.410,.740,10,20,45,50,15,4,11,9,.150,75,92,98,1,0,0,0,42,33,25',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'HS Stats Overall Radar',
            'original_filename' => 'radar.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => explode(',', $hdr),
            'row_count' => 4,
            'hs_profile_feed_slots' => ['performance_overall', 'approach_overall', 'impact_overall'],
        ]);

        $resolver = app(HsRangerTraitsSheetResolver::class);
        $all = $resolver->resolve($player, $user, null);
        $scoped = $resolver->resolve($player, $user, '1-2');

        $this->assertIsArray($all['radar'] ?? null);
        $this->assertIsArray($scoped['radar'] ?? null);
        $this->assertCount(5, $all['radar']['values']);
        $this->assertCount(5, $scoped['radar']['values']);
        $this->assertArrayHasKey('comp_scope', $all['radar']);
        $this->assertNull($all['radar']['comp_scope']);
        $this->assertSame('1-2', $scoped['radar']['comp_scope']);

        // CH% is inverted (lower chase is better); dropping the 7+ row changes the comp pool shape.
        $chNtileAll = $all['radar']['axes'][4]['ntile'] ?? null;
        $chNtileScoped = $scoped['radar']['axes'][4]['ntile'] ?? null;
        $this->assertNotNull($chNtileAll);
        $this->assertNotNull($chNtileScoped);
        $this->assertNotSame($chNtileAll, $chNtileScoped);
    }

    public function test_hs_radar_with_comp_scope_skips_upload_missing_rnds_column(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Will',
            'last_name' => 'Brick',
        ]);

        $hdrFull = 'PLAYER,YEAR,Rnds,PA,G,AVG,OBP,SLG,OPS,BB%,K%,SW%,SWDEC,CH%,PPA,SWM%,IZ SWM%,ISO,EV70,EV95,MAX EV,BIP 100+,BIP 105+,NITRO%,TX BAL%,GB%,FB%,LD%';
        $pathNoRnds = 'data-source-uploads/hs-radar-nornds-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathNoRnds, implode("\n", [
            'PLAYER,YEAR,PA,G,AVG,OBP,SLG,OPS,BB%,K%,SW%,SWDEC,CH%,PPA,SWM%,IZ SWM%,ISO,EV70,EV95,MAX EV,BIP 100+,BIP 105+,NITRO%,TX BAL%,GB%,FB%,LD%',
            '"BRICK, WILL",2024,100,1,.300,.400,.500,.900,10,20,45,50,25,4,12,10,.200,80,95,100,1,0,0,0,40,30,30',
        ]));

        $pathWithRnds = 'data-source-uploads/hs-radar-rnds-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathWithRnds, implode("\n", [
            $hdrFull,
            '"BRICK, WILL",2024,3-6,100,1,.300,.400,.500,.900,10,20,45,50,25,4,14,10,.200,80,95,100,1,0,0,0,40,30,30',
            '"LOW, SWM",2024,3-6,100,1,.280,.380,.480,.850,10,20,45,50,25,4,6,8,.200,80,95,100,1,0,0,0,40,30,30',
            '"HIGH, SWM",2024,3-6,100,1,.320,.420,.520,.950,10,20,45,50,25,4,18,14,.200,80,95,100,1,0,0,0,40,30,30',
            '"BRICK, WILL",2024,1-2,100,1,.300,.400,.500,.900,10,20,45,50,25,4,10,10,.200,80,95,100,1,0,0,0,40,30,30',
            '"ELITE, A",2024,1-2,100,1,.350,.450,.600,1.050,10,20,45,50,25,4,20,15,.200,80,95,100,1,0,0,0,40,30,30',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Wide no Rnds',
            'original_filename' => 'a.csv',
            'disk' => 'local',
            'path' => $pathNoRnds,
            'header_row' => explode(',', 'PLAYER,YEAR,PA,G,AVG,OBP,SLG,OPS,BB%,K%,SW%,SWDEC,CH%,PPA,SWM%,IZ SWM%,ISO,EV70,EV95,MAX EV,BIP 100+,BIP 105+,NITRO%,TX BAL%,GB%,FB%,LD%'),
            'row_count' => 1,
            'hs_profile_feed_slots' => ['performance_overall', 'approach_overall', 'impact_overall'],
        ]);
        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Overall with Rnds',
            'original_filename' => 'b.csv',
            'disk' => 'local',
            'path' => $pathWithRnds,
            'header_row' => explode(',', $hdrFull),
            'row_count' => 5,
            'hs_profile_feed_slots' => ['performance_overall', 'approach_overall', 'impact_overall'],
        ]);

        $resolver = app(HsRangerTraitsSheetResolver::class);
        $scoped36 = $resolver->resolve($player, $user, '3-6');
        $scoped12 = $resolver->resolve($player, $user, '1-2');

        $this->assertIsArray($scoped36['radar'] ?? null);
        $this->assertIsArray($scoped12['radar'] ?? null);
        $this->assertSame('3-6', $scoped36['radar']['comp_scope']);
        $this->assertSame('1-2', $scoped12['radar']['comp_scope']);
        $swm36 = $scoped36['radar']['axes'][1]['ntile'] ?? null;
        $swm12 = $scoped12['radar']['axes'][1]['ntile'] ?? null;
        $this->assertSame('14', (string) ($scoped36['radar']['axes'][1]['raw'] ?? ''));
        $this->assertSame('10', (string) ($scoped12['radar']['axes'][1]['raw'] ?? ''));
        $this->assertNotNull($swm36);
        $this->assertNotNull($swm12);
        // Lower SwM% is a better ntile; 10 vs {10,20} outranks 14 vs {6,14,18}.
        $this->assertGreaterThan($swm36, $swm12);
    }

    public function test_hs_radar_comp_scope_keeps_chart_when_player_row_has_no_bucket_tag(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Pat',
            'last_name' => 'Summers',
        ]);

        $hdr = 'PLAYER,YEAR,Rnds,PA,G,AVG,OBP,SLG,OPS,BB%,K%,SW%,SWDEC,CH%,PPA,SWM%,IZ SWM%,ISO,EV70,EV95,MAX EV,BIP 100+,BIP 105+,NITRO%,TX BAL%,GB%,FB%,LD%';
        $path = 'data-source-uploads/hs-radar-agg-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            $hdr,
            '"SUMMERS, PAT",2024,,100,1,.300,.400,.500,.900,10,20,45,50,25,4,12,10,.200,80,95,100,1,0,0,0,40,30,30',
            '"OTHER, A",2024,1-2,100,1,.280,.380,.480,.850,10,20,45,50,30,4,10,8,.200,80,95,100,1,0,0,0,45,30,25',
            '"OTHER, B",2024,1-2,100,1,.320,.420,.520,.950,10,20,45,50,20,4,14,12,.200,80,95,100,1,0,0,0,35,35,30',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'HS Overall',
            'original_filename' => 'radar.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => explode(',', $hdr),
            'row_count' => 3,
            'hs_profile_feed_slots' => ['performance_overall', 'approach_overall', 'impact_overall'],
        ]);

        $resolver = app(HsRangerTraitsSheetResolver::class);
        $scoped = $resolver->resolve($player, $user, '1-2');

        $this->assertIsArray($scoped['radar'] ?? null);
        $this->assertSame('1-2', $scoped['radar']['comp_scope']);
        $this->assertCount(5, $scoped['radar']['values']);
    }
}
