<?php

namespace Tests\Feature;

use App\Models\DataSourceUpload;
use App\Models\User;
use App\Support\CareerPgMasterUploadService;
use App\Support\CareerPgStatsAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CareerPgMasterStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_career_pg_dataset_not_created_without_performance_pg_slot(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/pg-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,YEAR,G,PA,AB,1B,2B,3B,HR,BB,K',
            '"DOE, JANE",2024,1,10,8,2,0,0,0,1,2',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'HS Stats - Perfect Game',
            'original_filename' => 'pg.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'YEAR', 'G', 'PA', 'AB', '1B', '2B', '3B', 'HR', 'BB', 'K'],
            'row_count' => 1,
            'hs_profile_feed_slots' => null,
        ]);

        $this->actingAs($user)->get(route('data-sources.index'))->assertOk();

        $this->assertSame(
            0,
            DataSourceUpload::query()->where('user_id', $user->id)->where('upload_kind', DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)->count()
        );
    }

    public function test_career_pg_aggregates_counts_and_recomputes_rate_columns(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/pg-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,YEAR,G,PA,AB,1B,2B,3B,HR,BB,K,AVG,OBP,SLG,OPS,ISO,BB%,K%',
            '"DOE, JANE",2023,5,20,18,10,0,0,0,4,5,0.500,0.700,0.500,1.200,0.000,0.200,0.250',
            '"DOE, JANE",2024,8,30,27,15,0,0,0,6,9,0.500,0.700,0.500,1.200,0.000,0.200,0.300',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'HS Stats - Perfect Game',
            'original_filename' => 'pg.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'YEAR', 'G', 'PA', 'AB', '1B', '2B', '3B', 'HR', 'BB', 'K', 'AVG', 'OBP', 'SLG', 'OPS', 'ISO', 'BB%', 'K%'],
            'row_count' => 2,
            'hs_profile_feed_slots' => ['performance_pg'],
        ]);

        $this->actingAs($user)->get(route('data-sources.index'))->assertOk();

        $career = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)
            ->first();
        $this->assertNotNull($career);
        $this->assertSame(CareerPgMasterUploadService::CAREER_DISPLAY_NAME, $career->name);

        $json = $this->actingAs($user)
            ->getJson(route('data-sources.uploads.table-data', ['dataSourceUpload' => $career->id]))
            ->assertOk()
            ->json();

        $this->assertCount(1, $json['rows']);
        $headers = $json['headers'];
        $row = $json['rows'][0];
        $map = array_combine($headers, $row);
        $this->assertNotFalse($map);
        $this->assertSame('Career', $map['YEAR']);
        $this->assertSame('13', $map['G']);
        $this->assertSame('50', $map['PA']);
        $this->assertSame('45', $map['AB']);
        $this->assertSame('25', $map['1B']);
        $this->assertSame('10', $map['BB']);
        $this->assertSame('14', $map['K']);
        $this->assertSame('.556', $map['AVG']);
        $this->assertSame('.700', $map['OBP']);
        $this->assertSame('.556', $map['SLG']);
        $this->assertSame('1.256', $map['OPS']);
        $this->assertSame('.000', $map['ISO']);
        $this->assertSame('20.0%', $map['BB%']);
        $this->assertSame('28.0%', $map['K%']);
    }

    public function test_career_pg_table_data_is_read_only_for_row_mutations(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/pg-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,YEAR,PA,1B,BB,K',
            '"DOE, JANE",2024,10,5,1,2',
        ]));

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'PG',
            'original_filename' => 'pg.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'YEAR', 'PA', '1B', 'BB', 'K'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['performance_pg'],
        ]);

        $this->actingAs($user)->get(route('data-sources.index'))->assertOk();
        $career = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)
            ->first();
        $this->assertNotNull($career);

        $this->actingAs($user)
            ->postJson(route('data-sources.uploads.rows.store', ['dataSourceUpload' => $career->id]), [
                'cells' => array_fill(0, count($career->header_row), 'x'),
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->patchJson(route('data-sources.uploads.rows.update', ['dataSourceUpload' => $career, 'ordinal' => 0]), [
                'player' => 'Other',
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->deleteJson(route('data-sources.uploads.rows.destroy', ['dataSourceUpload' => $career, 'ordinal' => 0]))
            ->assertStatus(422);
    }

    public function test_hs_profile_feed_assignments_mirror_source_slots_for_career_pg_master(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/pg-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "PLAYER\nA\n");

        $source = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'PG',
            'original_filename' => 'pg.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['performance_pg'],
        ]);

        $this->actingAs($user)->get(route('data-sources.index'))->assertOk();
        $career = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)
            ->first();
        $this->assertNotNull($career);
        $this->assertNull($career->hs_profile_feed_slots);

        $response = $this->actingAs($user)->patchJson(route('data-sources.uploads.settings', $source), [
            'hs_profile_feed_slots' => ['performance_pg'],
        ]);
        $response->assertOk();
        $byId = collect($response->json('hs_profile_feed_assignments'))->keyBy('id');
        $this->assertSame(['performance_pg'], $byId[$source->id]['hs_profile_feed_slots']);
        $this->assertSame(['performance_pg'], $byId[$career->id]['hs_profile_feed_slots']);
    }

    public function test_appending_row_to_pg_source_refreshes_career_pg_master_row_count(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/pg-append-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, implode("\n", [
            'PLAYER,YEAR,PA,BB,K',
            '"DOE, JANE",2024,10,2,3',
        ]));

        $source = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'HS Stats - Perfect Game',
            'original_filename' => 'pg.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'YEAR', 'PA', 'BB', 'K'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['performance_pg'],
        ]);

        $this->actingAs($user)->get(route('data-sources.index'))->assertOk();
        $career = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)
            ->first();
        $this->assertNotNull($career);
        $this->assertSame(1, $career->row_count);

        $this->actingAs($user)
            ->postJson(route('data-sources.uploads.rows.store', ['dataSourceUpload' => $source]), [
                'cells' => ['SMITH, BOB', '2025', '15', '3', '4'],
            ])
            ->assertOk();

        $source->refresh();
        $career->refresh();
        $expected = CareerPgStatsAggregator::fromSourceUpload($source)['row_count'];
        $this->assertSame(2, $expected);
        $this->assertSame($expected, $career->row_count);
    }
}
