<?php

namespace Tests\Feature;

use App\Models\DataSourceUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataSourceUploadBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_upload_show_url_redirects_to_index_with_dataset_query(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/test-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "a,b\n1,2\n3,4\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Rankings',
            'original_filename' => 'src.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['a', 'b'],
            'row_count' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('data-sources.uploads.show', $upload))
            ->assertRedirect(route('data-sources.index', ['dataset' => $upload->id]));
    }

    public function test_index_shows_dataset_tab_when_dataset_query_matches(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/test-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "a,b\n1,2\n3,4\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Rankings',
            'original_filename' => 'src.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['a', 'b'],
            'row_count' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('data-sources.index', ['dataset' => $upload->id]));

        $response->assertOk();
        $response->assertSee('Rankings', false);
        $response->assertSee('Filter Players', false);
        $response->assertSee('Append row', false);
        $response->assertSee('readOnlyById', false);
    }

    public function test_authenticated_user_can_fetch_table_data_json(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/test-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "a,b\n1,2\n3,4\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Rankings',
            'original_filename' => 'src.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['a', 'b'],
            'row_count' => 2,
        ]);

        $response = $this->actingAs($user)->getJson(route('data-sources.uploads.table-data', $upload));

        $response->assertOk();
        $response->assertJsonStructure([
            'headers',
            'rows',
            'row_ordinals',
            'column_order',
            'heat_rules',
            'heat_column_stats',
            'heat_row_pa_ok',
        ]);
        $response->assertJsonPath('headers', ['a', 'b']);
        $response->assertJsonPath('totalRows', 2);
        $response->assertJsonPath('rows.0', ['1', '2']);
    }

    public function test_table_data_moves_player_column_first(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/player-order-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n2026,BETA,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 2,
        ]);

        $response = $this->actingAs($user)->getJson(route('data-sources.uploads.table-data', $upload));

        $response->assertOk();
        $response->assertJsonPath('headers', ['PLAYER', 'YEAR', 'STAT']);
        $response->assertJsonPath('rows.0', ['ALPHA', '2025', '10']);
        $response->assertJsonPath('rows.1', ['BETA', '2026', '20']);
    }

    public function test_table_data_filters_by_player_substring(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/player-filter-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n2026,BETA,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 2,
        ]);

        $url = route('data-sources.uploads.table-data', $upload).'?filter=beta';

        $response = $this->actingAs($user)->getJson($url);

        $response->assertOk();
        $response->assertJsonPath('totalRows', 1);
        $response->assertJsonPath('filter_active', true);
        $response->assertJsonPath('rows.0', ['BETA', '2026', '20']);
    }

    public function test_table_data_sorts_by_column_numeric_asc(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/sort-num-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,A,3\n2026,B,1\n2027,C,2\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 3,
        ]);

        $url = route('data-sources.uploads.table-data', $upload).'?'.http_build_query([
            'sort_column' => 2,
            'sort_direction' => 'asc',
        ]);

        $response = $this->actingAs($user)->getJson($url);

        $response->assertOk();
        $response->assertJsonPath('headers', ['PLAYER', 'YEAR', 'STAT']);
        $response->assertJsonPath('sort.column', 2);
        $response->assertJsonPath('sort.direction', 'asc');
        $response->assertJsonPath('rows.0', ['B', '2026', '1']);
        $response->assertJsonPath('rows.1', ['C', '2027', '2']);
        $response->assertJsonPath('rows.2', ['A', '2025', '3']);
    }

    public function test_table_data_sorts_filtered_rows(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/sort-filter-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,A,10\n2026,B,30\n2027,C,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 3,
        ]);

        $url = route('data-sources.uploads.table-data', $upload).'?'.http_build_query([
            'players' => ['A', 'C'],
            'sort_column' => 2,
            'sort_direction' => 'desc',
        ]);

        $response = $this->actingAs($user)->getJson($url);

        $response->assertOk();
        $response->assertJsonPath('totalRows', 2);
        $response->assertJsonPath('rows.0', ['C', '2027', '20']);
        $response->assertJsonPath('rows.1', ['A', '2025', '10']);
    }

    public function test_table_data_filters_by_column_thresholds(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/thresh-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,A,10\n2026,B,5\n2027,C,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 3,
        ]);

        $url = route('data-sources.uploads.table-data', $upload).'?'.http_build_query([
            'column_thresholds' => json_encode([
                ['col' => 2, 'min' => 12],
            ]),
        ]);

        $response = $this->actingAs($user)->getJson($url);

        $response->assertOk();
        $response->assertJsonPath('headers', ['PLAYER', 'YEAR', 'STAT']);
        $response->assertJsonPath('filter_active', true);
        $response->assertJsonPath('totalRows', 1);
        $response->assertJsonPath('rows.0', ['C', '2027', '20']);
    }

    public function test_table_data_thresholds_combine_with_players(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/thresh-players-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,A,10\n2026,B,30\n2027,C,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 3,
        ]);

        $url = route('data-sources.uploads.table-data', $upload).'?'.http_build_query([
            'players' => ['A', 'C'],
            'column_thresholds' => json_encode([
                ['col' => 2, 'max' => 15],
            ]),
        ]);

        $response = $this->actingAs($user)->getJson($url);

        $response->assertOk();
        $response->assertJsonPath('totalRows', 1);
        $response->assertJsonPath('filter_active', true);
        $response->assertJsonPath('rows.0', ['A', '2025', '10']);
    }

    public function test_table_data_filters_by_multiple_players_exact(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/player-multi-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n2026,BETA,20\n2027,GAMMA,30\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 3,
        ]);

        $url = route('data-sources.uploads.table-data', $upload).'?'.http_build_query([
            'players' => ['ALPHA', 'GAMMA'],
        ]);

        $response = $this->actingAs($user)->getJson($url);

        $response->assertOk();
        $response->assertJsonPath('totalRows', 2);
        $response->assertJsonPath('filter_active', true);
        $response->assertJsonPath('rows.0', ['ALPHA', '2025', '10']);
        $response->assertJsonPath('rows.1', ['GAMMA', '2027', '30']);
    }

    public function test_player_names_returns_sorted_unique_list(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/player-names-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n2026,BETA,20\n2027,GAMMA,30\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 3,
        ]);

        $response = $this->actingAs($user)->getJson(route('data-sources.uploads.player-names', $upload));

        $response->assertOk();
        $response->assertJsonPath('names', ['ALPHA', 'BETA', 'GAMMA']);
    }

    public function test_user_cannot_fetch_another_users_player_names(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $path = 'data-source-uploads/other-names-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 1,
        ]);

        $this->actingAs($other)->getJson(route('data-sources.uploads.player-names', $upload))->assertNotFound();
    }

    public function test_user_cannot_open_another_users_upload_show(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $path = 'data-source-uploads/other-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "x\ny\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private',
            'original_filename' => 'p.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['x'],
            'row_count' => 1,
        ]);

        $this->actingAs($other)->get(route('data-sources.uploads.show', $upload))->assertNotFound();
    }

    public function test_user_cannot_fetch_another_users_table_data(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $path = 'data-source-uploads/other-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "x\ny\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private',
            'original_filename' => 'p.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['x'],
            'row_count' => 1,
        ]);

        $this->actingAs($other)->getJson(route('data-sources.uploads.table-data', $upload))->assertNotFound();
    }

    public function test_can_patch_column_order_after_player_first(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/col-order-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n2026,BETA,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 2,
        ]);

        $this->actingAs($user)->patchJson(route('data-sources.uploads.settings', $upload), [
            'column_order' => [0, 2, 1],
        ])->assertOk();

        $this->actingAs($user)->getJson(route('data-sources.uploads.table-data', $upload))
            ->assertOk()
            ->assertJsonPath('headers', ['PLAYER', 'STAT', 'YEAR'])
            ->assertJsonPath('rows.0', ['ALPHA', '10', '2025']);
    }

    public function test_can_enable_heat_rules_and_persist_column_stats(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/heat-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n2026,BETA,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 2,
        ]);

        $this->actingAs($user)->patchJson(route('data-sources.uploads.settings', $upload), [
            'heat_rules' => [
                'STAT' => ['enabled' => true, 'higher_is_better' => true],
            ],
        ])->assertOk();

        $upload->refresh();
        $this->assertIsArray($upload->heat_column_stats);
        $this->assertArrayHasKey('STAT', $upload->heat_column_stats);
        $this->assertEqualsWithDelta(10.0, (float) $upload->heat_column_stats['STAT']['min'], 0.00001);
        $this->assertEqualsWithDelta(20.0, (float) $upload->heat_column_stats['STAT']['max'], 0.00001);
        $this->assertEqualsWithDelta(15.0, (float) $upload->heat_column_stats['STAT']['median'], 0.00001);

        $this->actingAs($user)->getJson(route('data-sources.uploads.table-data', $upload))
            ->assertOk()
            ->assertJsonPath('heat_column_stats.STAT.min', 10)
            ->assertJsonPath('heat_column_stats.STAT.max', 20)
            ->assertJsonPath('heat_column_stats.STAT.median', 15);
    }

    public function test_can_rename_player_in_csv_by_row_ordinal(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/rename-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n2026,BETA,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 2,
        ]);

        $this->actingAs($user)->patchJson(
            route('data-sources.uploads.rows.update', ['dataSourceUpload' => $upload, 'ordinal' => 0]),
            ['player' => 'GAMMA']
        )->assertOk();

        $csv = Storage::disk('local')->get($path);
        $this->assertStringContainsString('GAMMA', $csv);
        $this->assertStringNotContainsString(',ALPHA,', $csv);
    }

    public function test_can_append_data_row_to_csv(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/append-row-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 1,
        ]);

        $this->actingAs($user)->postJson(route('data-sources.uploads.rows.store', $upload), [
            'cells' => ['2026', 'DELTA', '42'],
        ])->assertOk()->assertJsonPath('row_count', 2);

        $upload->refresh();
        $this->assertSame(2, $upload->row_count);
        $csv = Storage::disk('local')->get($path);
        $this->assertStringContainsString('DELTA', $csv);
        $this->assertStringContainsString('42', $csv);
    }

    public function test_append_row_requires_player_column(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/append-reject-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 1,
        ]);

        $this->actingAs($user)->postJson(route('data-sources.uploads.rows.store', $upload), [
            'cells' => ['2026', '', '5'],
        ])->assertUnprocessable();
    }

    public function test_can_delete_data_row_by_ordinal(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/del-row-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n2026,BETA,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Stats',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 2,
        ]);

        $this->actingAs($user)->deleteJson(
            route('data-sources.uploads.rows.destroy', ['dataSourceUpload' => $upload, 'ordinal' => 0])
        )->assertOk()->assertJsonPath('row_count', 1);

        $upload->refresh();
        $this->assertSame(1, $upload->row_count);
        $csv = Storage::disk('local')->get($path);
        $this->assertStringNotContainsString('ALPHA', $csv);
        $this->assertStringContainsString('BETA', $csv);
    }

    public function test_user_cannot_delete_row_from_another_users_upload(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $path = 'data-source-uploads/del-other-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "YEAR,PLAYER,STAT\n2025,ALPHA,10\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private',
            'original_filename' => 'stats.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['YEAR', 'PLAYER', 'STAT'],
            'row_count' => 1,
        ]);

        $this->actingAs($other)->deleteJson(
            route('data-sources.uploads.rows.destroy', ['dataSourceUpload' => $upload, 'ordinal' => 0])
        )->assertNotFound();
    }

    public function test_user_can_delete_entire_upload_and_file_is_removed(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/wipe-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "a,b\n1,2\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'To remove',
            'original_filename' => 'x.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['a', 'b'],
            'row_count' => 1,
        ]);

        $this->actingAs($user)->deleteJson(route('data-sources.uploads.delete', $upload), [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('data_source_uploads', ['id' => $upload->id]);
        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    public function test_user_cannot_delete_another_users_upload(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $path = 'data-source-uploads/other-del-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "x\ny\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private',
            'original_filename' => 'p.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['x'],
            'row_count' => 1,
        ]);

        $this->actingAs($other)->deleteJson(route('data-sources.uploads.delete', $upload))->assertNotFound();
        $this->assertDatabaseHas('data_source_uploads', ['id' => $upload->id]);
        $this->assertTrue(Storage::disk('local')->exists($path));
    }

    public function test_group_column_values_returns_distinct_display_column_values(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/grp-val-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "Player,Type,VAL\nA,Fastball,1\nA,Breaking,2\nB,Fastball,3\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Pitch types',
            'original_filename' => 'p.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['Player', 'Type', 'VAL'],
            'row_count' => 3,
        ]);

        $url = route('data-sources.uploads.group-values', $upload).'?'.http_build_query([
            'group_column' => 1,
        ]);

        $this->actingAs($user)->getJson($url)
            ->assertOk()
            ->assertJsonPath('values', ['Breaking', 'Fastball']);
    }

    public function test_table_data_filters_rows_by_group_column_and_value(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/grp-filt-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "Player,Type,VAL\nA,Fastball,1\nA,Breaking,2\nB,Fastball,3\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Pitch types',
            'original_filename' => 'p.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['Player', 'Type', 'VAL'],
            'row_count' => 3,
        ]);

        $url = route('data-sources.uploads.table-data', $upload).'?'.http_build_query([
            'group_column' => 1,
            'group_value' => 'Fastball',
        ]);

        $this->actingAs($user)->getJson($url)
            ->assertOk()
            ->assertJsonPath('totalRows', 2)
            ->assertJsonPath('rows.0', ['A', 'Fastball', '1'])
            ->assertJsonPath('rows.1', ['B', 'Fastball', '3'])
            ->assertJsonPath('group.column', 1)
            ->assertJsonPath('group.active', true);
    }

    public function test_table_data_group_filter_uses_subset_heat_column_stats(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/grp-heat-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "Player,Kind,STAT\nA,FB,10\nB,BB,100\nC,FB,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Split heat',
            'original_filename' => 's.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['Player', 'Kind', 'STAT'],
            'row_count' => 3,
        ]);

        $this->actingAs($user)->patchJson(route('data-sources.uploads.settings', $upload), [
            'heat_rules' => [
                'STAT' => ['enabled' => true, 'higher_is_better' => true],
            ],
        ])->assertOk();

        $upload->refresh();
        $this->assertEqualsWithDelta(10.0, (float) $upload->heat_column_stats['STAT']['min'], 0.00001);
        $this->assertEqualsWithDelta(100.0, (float) $upload->heat_column_stats['STAT']['max'], 0.00001);

        $url = route('data-sources.uploads.table-data', $upload).'?'.http_build_query([
            'group_column' => 1,
            'group_value' => 'FB',
        ]);

        $this->actingAs($user)->getJson($url)
            ->assertOk()
            ->assertJsonPath('heat_column_stats.STAT.min', 10)
            ->assertJsonPath('heat_column_stats.STAT.max', 20);
    }

    public function test_table_data_group_plus_player_filter_keeps_group_scoped_heat_stats(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/grp-heat-player-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "Player,Kind,STAT\nA,FB,10\nB,BB,100\nC,FB,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'Split heat + player',
            'original_filename' => 's.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['Player', 'Kind', 'STAT'],
            'row_count' => 3,
        ]);

        $this->actingAs($user)->patchJson(route('data-sources.uploads.settings', $upload), [
            'heat_rules' => [
                'STAT' => ['enabled' => true, 'higher_is_better' => true],
            ],
        ])->assertOk();

        $url = route('data-sources.uploads.table-data', $upload).'?'.http_build_query([
            'group_column' => 1,
            'group_value' => 'FB',
            'players' => ['A'],
        ]);

        $this->actingAs($user)->getJson($url)
            ->assertOk()
            ->assertJsonPath('totalRows', 1)
            ->assertJsonPath('rows.0', ['A', 'FB', '10'])
            ->assertJsonPath('heat_column_stats.STAT.min', 10)
            ->assertJsonPath('heat_column_stats.STAT.max', 20);
    }

    public function test_heat_column_stats_use_only_rows_meeting_min_pa(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/heat-pa-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "PLAYER,PA,STAT\nA,5,1\nB,100,10\nC,200,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'PA heat',
            'original_filename' => 'p.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'PA', 'STAT'],
            'row_count' => 3,
            'dataset_browse_settings' => [
                'players' => [],
                'column_thresholds' => [],
                'group_column' => null,
                'group_value' => null,
                'heat_min_pa' => 50,
            ],
        ]);

        $this->actingAs($user)->patchJson(route('data-sources.uploads.settings', $upload), [
            'heat_rules' => [
                'STAT' => ['enabled' => true, 'higher_is_better' => true],
            ],
        ])->assertOk();

        $upload->refresh();
        $this->assertEqualsWithDelta(10.0, (float) $upload->heat_column_stats['STAT']['min'], 0.00001);
        $this->assertEqualsWithDelta(20.0, (float) $upload->heat_column_stats['STAT']['max'], 0.00001);

        $this->actingAs($user)->getJson(route('data-sources.uploads.table-data', $upload))
            ->assertOk()
            ->assertJsonPath('heat_pa_qualifier.min', 50)
            ->assertJsonPath('heat_pa_qualifier.column_index', 1)
            ->assertJsonPath('heat_row_pa_ok.0', false)
            ->assertJsonPath('heat_row_pa_ok.1', true)
            ->assertJsonPath('heat_row_pa_ok.2', true);
    }

    public function test_table_data_heat_min_pa_query_recomputes_stats_without_save(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/heat-pa-q-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "PLAYER,PA,STAT\nA,5,1\nB,100,10\nC,200,20\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'PA heat q',
            'original_filename' => 'p.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'PA', 'STAT'],
            'row_count' => 3,
        ]);

        $this->actingAs($user)->patchJson(route('data-sources.uploads.settings', $upload), [
            'heat_rules' => [
                'STAT' => ['enabled' => true, 'higher_is_better' => true],
            ],
        ])->assertOk();

        $upload->refresh();
        $this->assertEqualsWithDelta(1.0, (float) $upload->heat_column_stats['STAT']['min'], 0.00001);

        $url = route('data-sources.uploads.table-data', $upload).'?'.http_build_query([
            'heat_min_pa' => 50,
        ]);

        $this->actingAs($user)->getJson($url)
            ->assertOk()
            ->assertJsonPath('heat_column_stats.STAT.min', 10)
            ->assertJsonPath('heat_column_stats.STAT.max', 20);
    }

    public function test_table_data_heat_row_pa_ok_is_null_without_min_pa(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/hrpo-null-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "PLAYER,PA,STAT\nA,100,10\n");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'name' => 'x',
            'original_filename' => 'x.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'PA', 'STAT'],
            'row_count' => 1,
        ]);

        $this->actingAs($user)->getJson(route('data-sources.uploads.table-data', $upload))
            ->assertOk()
            ->assertJsonPath('heat_row_pa_ok', null);
    }
}
