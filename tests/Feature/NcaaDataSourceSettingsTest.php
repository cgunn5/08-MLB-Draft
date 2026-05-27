<?php

namespace Tests\Feature;

use App\Models\DataSourceUpload;
use App\Models\Player;
use App\Models\User;
use App\Support\HsRangerTraitsDisplay;
use App\Support\NcaaRangerTraitsSheetResolver;
use App\Support\PlayerSheetPlaceholder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NcaaDataSourceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_ncaa_dataset_persists_profile_feed_slots_and_browse_settings(): void
    {
        $user = User::factory()->admin()->create();
        $path = 'data-source-uploads/ncaa-settings-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "PLAYER\nTEST\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Overall',
            'original_filename' => 'o.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER'],
            'row_count' => 1,
            'ncaa_profile_feed_slots' => null,
        ]);

        $response = $this->actingAs($user)->patchJson(route('ncaa-data-sources.uploads.settings', $upload), [
            'dataset_browse_settings' => [
                'players' => [],
                'column_thresholds' => [],
                'group_column' => null,
                'group_value' => null,
                'heat_min_pa' => 200,
                'heat_volume_header' => '__auto__',
            ],
            'ncaa_profile_feed_slots' => ['performance_ncaa', 'approach_ncaa', 'engine_overall'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonStructure(['ncaa_profile_feed_assignments']);

        $upload->refresh();
        $this->assertSame(['performance_ncaa', 'approach_ncaa', 'engine_overall'], $upload->ncaa_profile_feed_slots);
        $this->assertIsArray($upload->dataset_browse_settings);
        $this->assertEquals(200, $upload->dataset_browse_settings['heat_min_pa']);
    }

    public function test_can_rename_ncaa_upload_display_name(): void
    {
        $user = User::factory()->admin()->create();
        $path = 'data-source-uploads/ncaa-rename-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "PLAYER\nA\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Old label',
            'original_filename' => 'o.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER'],
            'row_count' => 1,
        ]);

        $response = $this->actingAs($user)->patchJson(route('ncaa-data-sources.uploads.settings', $upload), [
            'name' => '  New label  ',
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('name', 'New label');

        $upload->refresh();
        $this->assertSame('New label', $upload->name);
    }

    public function test_ncaa_resolver_uses_saved_slots_for_assigned_upload(): void
    {
        $user = User::factory()->admin()->create();
        $path = 'data-source-uploads/ncaa-resolver-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,Draft Year,PA,PA (N-1),PA (N-2),OPS,OPS (N-1),OPS (N-2)',
            '"DOE, JANE",2026,10,20,30,.1,.2,.3',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Overall',
            'original_filename' => 'o.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => [
                'PLAYER', 'Draft Year', 'PA', 'PA (N-1)', 'PA (N-2)', 'OPS', 'OPS (N-1)', 'OPS (N-2)',
            ],
            'row_count' => 1,
            'ncaa_profile_feed_slots' => ['performance_ncaa'],
        ]);

        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);
        $this->assertCount(3, $sheet['ncaa_perf_ncaa']);
    }

    public function test_ncaa_resolver_platoon_block_returns_three_year_rows(): void
    {
        $user = User::factory()->admin()->create();
        $path = 'data-source-uploads/ncaa-platoon-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,Year,OPS vs R,ISO vs R,K/BB vs R,OPS vs L,ISO vs L,K/BB vs L',
            '"DOE, JANE",2026,0.800,0.150,2.5,0.700,0.120,2.0',
            '"DOE, JANE",2025,0.750,0.140,2.4,0.650,0.110,1.9',
            '"DOE, JANE",2024,0.700,0.130,2.3,0.600,0.100,1.8',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Overall',
            'original_filename' => 'o.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => [
                'PLAYER', 'Year', 'OPS vs R', 'ISO vs R', 'K/BB vs R', 'OPS vs L', 'ISO vs L', 'K/BB vs L',
            ],
            'row_count' => 3,
            'ncaa_profile_feed_slots' => ['platoon_ncaa'],
        ]);

        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);
        $this->assertCount(3, $sheet['ncaa_platoon']);
        $this->assertSame('2026', $sheet['ncaa_platoon'][0]['year'] ?? '');
        $this->assertSame('.800', $sheet['ncaa_platoon'][0]['ops_vs_r'] ?? '');
        $this->assertSame('2.50', $sheet['ncaa_platoon'][0]['k_bb_vs_r'] ?? '');
    }

    public function test_ncaa_resolver_adjustability_returns_three_pitch_tables_with_year_rows(): void
    {
        $user = User::factory()->admin()->create();
        $path = 'data-source-uploads/ncaa-adjust-pitch-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,Year,Pitch,P,BIPx,OPS,ISO,EV95,GB%,SwM%,IZSwM%,CH%',
            '"DOE, JANE",2026,FB,100,50,.800,.200,95,40,10,20,30',
            '"DOE, JANE",2025,FB,90,45,.750,.180,94,41,11,21,31',
            '"DOE, JANE",2024,FB,80,40,.700,.160,93,42,12,22,32',
            '"DOE, JANE",2026,CB,20,10,.500,.100,88,50,5,10,15',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Pitch Types',
            'original_filename' => 'pt.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => [
                'PLAYER', 'Year', 'Pitch', 'P', 'BIPx', 'OPS', 'ISO', 'EV95', 'GB%', 'SwM%', 'IZSwM%', 'CH%',
            ],
            'row_count' => 4,
            'ncaa_profile_feed_slots' => ['adjustability_overall'],
        ]);

        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);
        $adj = $sheet['ncaa_adjust_pitch'];
        $this->assertCount(3, $adj);
        $this->assertSame('FB', $adj[0]['pitch'] ?? null);
        $this->assertSame('BB', $adj[1]['pitch'] ?? null);
        $this->assertSame('OS', $adj[2]['pitch'] ?? null);

        $fbRows = $adj[0]['rows'] ?? [];
        $this->assertCount(3, $fbRows);
        $this->assertSame('2026', $fbRows[0]['year'] ?? '');
        $this->assertSame('2025', $fbRows[1]['year'] ?? '');
        $this->assertSame('2024', $fbRows[2]['year'] ?? '');
        $this->assertSame('.800', HsRangerTraitsDisplay::formatThreeDecimals($fbRows[0]['ops'] ?? null));

        $bbRows = $adj[1]['rows'] ?? [];
        $this->assertCount(1, $bbRows);
        $this->assertSame('2026', $bbRows[0]['year'] ?? '');
        $this->assertSame('.500', HsRangerTraitsDisplay::formatThreeDecimals($bbRows[0]['ops'] ?? null));

        $osRows = $adj[2]['rows'] ?? [];
        $this->assertCount(1, $osRows);
        $this->assertSame(PlayerSheetPlaceholder::CELL, $osRows[0]['year'] ?? null);
    }

    public function test_ncaa_resolver_adjustability_maps_gb_r_to_gb_pct(): void
    {
        $user = User::factory()->admin()->create();
        $path = 'data-source-uploads/ncaa-adj-gbr-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,Year,Pitch,P,BIPx,OPS,ISO,EV95,GB_r,SwM%,IZSwM%,CH%',
            '"DOE, JANE",2026,FB,100,50,.800,.200,95,41.5,10,20,30',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Pitch Types',
            'original_filename' => 'pt.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => [
                'PLAYER', 'Year', 'Pitch', 'P', 'BIPx', 'OPS', 'ISO', 'EV95', 'GB_r', 'SwM%', 'IZSwM%', 'CH%',
            ],
            'row_count' => 1,
            'ncaa_profile_feed_slots' => ['adjustability_pitch'],
        ]);

        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);
        $gbPct = ($sheet['ncaa_adjust_pitch'][0]['rows'] ?? [])[0]['gb_pct'] ?? null;
        $this->assertSame('41.5', $gbPct);
    }

    public function test_ncaa_resolver_adjustability_prefers_upload_named_pitch_types_when_merged_last(): void
    {
        $user = User::factory()->admin()->create();

        $pathOverall = 'data-source-uploads/ncaa-adj-overall-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathOverall, implode("\n", [
            'PLAYER,Year,Pitch,P,BIPx,OPS,ISO,EV95,GB%,SwM%,IZSwM%,CH%',
            '"DOE, JANE",2026,FB,1,1,.100,.010,90,10,1,2,3',
        ]));
        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Overall',
            'original_filename' => 'o.csv',
            'disk' => 'local',
            'path' => $pathOverall,
            'header_row' => [
                'PLAYER', 'Year', 'Pitch', 'P', 'BIPx', 'OPS', 'ISO', 'EV95', 'GB%', 'SwM%', 'IZSwM%', 'CH%',
            ],
            'row_count' => 1,
            'ncaa_profile_feed_slots' => ['adjustability_overall'],
        ]);

        $pathPitchTypes = 'data-source-uploads/ncaa-adj-pt-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathPitchTypes, implode("\n", [
            'PLAYER,Year,Pitch,P,BIPx,OPS,ISO,EV95,GB%,SwM%,IZSwM%,CH%',
            '"DOE, JANE",2026,FB,200,100,.900,.250,98,35,12,18,25',
        ]));
        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'NCAA Pitch Types',
            'original_filename' => 'pt.csv',
            'disk' => 'local',
            'path' => $pathPitchTypes,
            'header_row' => [
                'PLAYER', 'Year', 'Pitch', 'P', 'BIPx', 'OPS', 'ISO', 'EV95', 'GB%', 'SwM%', 'IZSwM%', 'CH%',
            ],
            'row_count' => 1,
            'ncaa_profile_feed_slots' => ['adjustability_overall'],
        ]);

        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);
        $fb = ($sheet['ncaa_adjust_pitch'][0]['rows'] ?? [])[0] ?? [];
        $this->assertSame('.900', HsRangerTraitsDisplay::formatThreeDecimals($fb['ops'] ?? null));
    }

    public function test_ncaa_resolver_adjustability_accepts_game_year_and_pitch_group_headers(): void
    {
        $user = User::factory()->admin()->create();
        $path = 'data-source-uploads/ncaa-adj-gameyr-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,Game Year,Pitch Group,P,BIPx,OPS,ISO,EV95,GB%,SwM%,IZSwM%,CH%',
            '"DOE, JANE",2026-27,Fastball,100,50,.810,.210,95,40,10,20,30',
            '"DOE, JANE",2025-26,Fastball,90,45,.760,.190,94,41,11,21,31',
            '"DOE, JANE",2024-25,Fastball,80,40,.710,.170,93,42,12,22,32',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Pitch Types',
            'original_filename' => 'pt.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => [
                'PLAYER', 'Game Year', 'Pitch Group', 'P', 'BIPx', 'OPS', 'ISO', 'EV95', 'GB%', 'SwM%', 'IZSwM%', 'CH%',
            ],
            'row_count' => 3,
            'ncaa_profile_feed_slots' => ['adjustability_pitch'],
        ]);

        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);
        $fbRows = $sheet['ncaa_adjust_pitch'][0]['rows'] ?? [];
        $this->assertCount(3, $fbRows);
        $this->assertSame('2026-27', $fbRows[0]['year'] ?? '');
        $this->assertSame('2025-26', $fbRows[1]['year'] ?? '');
        $this->assertSame('2024-25', $fbRows[2]['year'] ?? '');
    }

    public function test_ncaa_adjustability_merges_split_pitch_csvs_via_pitch_type_feed(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $headers = ['PLAYER', 'Year', 'P', 'BIPx', 'OPS', 'ISO', 'EV95', 'GB%', 'SwM%', 'IZSwM%', 'CH%'];
        $specs = [
            ['feed' => 'FB', 'ops' => '.900', 'p' => '200'],
            ['feed' => 'BB', 'ops' => '.700', 'p' => '50'],
            ['feed' => 'OS', 'ops' => '.650', 'p' => '120'],
        ];

        foreach ($specs as $spec) {
            $path = 'data-source-uploads/ncaa-split-'.strtolower($spec['feed']).'-'.uniqid('', true).'.csv';
            Storage::disk('local')->put($path, implode("\n", [
                implode(',', $headers),
                '"DOE, JANE",2026,'.$spec['p'].',100,'.$spec['ops'].',.200,95,40,10,20,30',
            ]));
            DataSourceUpload::query()->create([
                'user_id' => $user->id,
                'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
                'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
                'name' => $spec['feed'].' pitch',
                'original_filename' => strtolower($spec['feed']).'.csv',
                'disk' => 'local',
                'path' => $path,
                'header_row' => $headers,
                'row_count' => 1,
                'ncaa_profile_feed_slots' => ['adjustability_pitch'],
                'pitch_type_feed' => $spec['feed'],
            ]);
        }

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);
        $blocks = $sheet['ncaa_adjust_pitch'] ?? [];
        $this->assertCount(3, $blocks);
        $this->assertSame('FB', $blocks[0]['pitch'] ?? null);
        $this->assertSame('.900', ($blocks[0]['rows'][0]['ops'] ?? null));
        $this->assertSame('BB', $blocks[1]['pitch'] ?? null);
        $this->assertSame('.700', ($blocks[1]['rows'][0]['ops'] ?? null));
        $this->assertSame('OS', $blocks[2]['pitch'] ?? null);
        $this->assertSame('.650', ($blocks[2]['rows'][0]['ops'] ?? null));
    }

    public function test_ncaa_resolver_overall_radar_respects_comp_scope(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $hdr = 'PLAYER,YEAR,Rnds,PA,G,AVG,OBP,SLG,OPS,BB%,K%,SW%,SWDEC,CH%,PPA,SWM%,IZ SWM%,ISO,EV70,EV95,MAX EV,BIP 100+,BIP 105+,NITRO%,TX BAL%,GB%,FB%,LD%';
        $path = 'data-source-uploads/ncaa-radar-comp-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            $hdr,
            '"DOE, JANE",2024,1-2,100,1,.300,.400,.500,.900,10,20,45,50,25,4,12,10,.200,80,95,100,1,0,0,0,40,30,30',
            '"LOW, OPS",2024,1-2,100,1,.200,.250,.300,.500,10,20,45,50,30,4,8,8,.100,70,85,90,1,0,0,0,50,25,25',
            '"HIGH, OPS",2024,1-2,100,1,.400,.450,.600,1.100,10,20,45,50,20,4,15,12,.250,85,100,105,1,0,0,0,35,35,30',
            '"OTHER, X",2024,7+,100,1,.250,.330,.410,.740,10,20,45,50,15,4,11,9,.150,75,92,98,1,0,0,0,42,33,25',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'NCAA Overall',
            'original_filename' => 'radar.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => explode(',', $hdr),
            'row_count' => 4,
            'ncaa_profile_feed_slots' => ['performance_ncaa'],
        ]);

        $resolver = app(NcaaRangerTraitsSheetResolver::class);
        $all = $resolver->resolve($player, $user, null);
        $scoped = $resolver->resolve($player, $user, '1-2');

        $this->assertIsArray($all['radar'] ?? null);
        $this->assertIsArray($scoped['radar'] ?? null);
        $this->assertCount(5, $all['radar']['values']);
        $this->assertCount(5, $scoped['radar']['values']);
        $this->assertNull($all['radar']['comp_scope']);
        $this->assertSame('1-2', $scoped['radar']['comp_scope']);
        $chNtileAll = $all['radar']['axes'][4]['ntile'] ?? null;
        $chNtileScoped = $scoped['radar']['axes'][4]['ntile'] ?? null;
        $this->assertNotNull($chNtileAll);
        $this->assertNotNull($chNtileScoped);
        $this->assertNotSame($chNtileAll, $chNtileScoped);
    }

    public function test_ncaa_resolver_comp_scope_matches_unicode_dash_in_rnds_column(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $bucket12 = "1\u{2013}2";
        $hdr = 'PLAYER,YEAR,Rnds,PA,G,AVG,OBP,SLG,OPS,BB%,K%,SW%,SWDEC,CH%,PPA,SWM%,IZ SWM%,ISO,EV70,EV95,MAX EV,BIP 100+,BIP 105+,NITRO%,TX BAL%,GB%,FB%,LD%';
        $path = 'data-source-uploads/ncaa-radar-comp-dash-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            $hdr,
            '"DOE, JANE",2024,'.$bucket12.',100,1,.300,.400,.500,.900,10,20,45,50,25,4,12,10,.200,80,95,100,1,0,0,0,0,40,30,30',
            '"LOW, OPS",2024,'.$bucket12.',100,1,.200,.250,.300,.500,10,20,45,50,30,4,8,8,.100,70,85,90,1,0,0,0,0,50,25,25',
            '"HIGH, OPS",2024,'.$bucket12.',100,1,.400,.450,.600,1.100,10,20,45,50,20,4,15,12,.250,85,100,105,1,0,0,0,0,35,35,30',
            '"OTHER, X",2024,7+,100,1,.250,.330,.410,.740,10,20,45,50,15,4,11,9,.150,75,92,98,1,0,0,0,0,42,33,25',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'NCAA Overall',
            'original_filename' => 'radar.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => explode(',', $hdr),
            'row_count' => 4,
            'ncaa_profile_feed_slots' => ['performance_ncaa'],
        ]);

        $scoped = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, '1-2');

        $this->assertIsArray($scoped['radar'] ?? null);
        $this->assertSame('1-2', $scoped['radar']['comp_scope']);
        $this->assertCount(5, $scoped['radar']['values']);
    }
}
