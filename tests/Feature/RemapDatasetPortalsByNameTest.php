<?php

namespace Tests\Feature;

use App\Models\DataSourceUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RemapDatasetPortalsByNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_moves_ncaa_pitch_types_from_hs_to_ncaa_and_remaps_pitch_slot(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/remap-ncaa-pitch-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "Pitch,PA\nFB,10");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_HS,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'NCAA - Pitch Types',
            'original_filename' => 'p.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['Pitch', 'PA'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['adjustability_pitch'],
            'ncaa_profile_feed_slots' => null,
        ]);

        $this->artisan('app:remap-dataset-portals-by-name', ['--user' => (string) $user->id])
            ->assertSuccessful();

        $upload->refresh();
        $this->assertSame(DataSourceUpload::PORTAL_NCAA, $upload->dataset_portal);
        $this->assertNull($upload->hs_profile_feed_slots);
        $this->assertSame(['adjustability_pitch'], $upload->ncaa_profile_feed_slots);
    }

    public function test_moves_hs_overall_from_ncaa_to_hs_and_remaps_slots(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/remap-hs-overall-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "PLAYER,YEAR,PA\nA,2024,1");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'HS - Overall',
            'original_filename' => 'o.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'YEAR', 'PA'],
            'row_count' => 1,
            'hs_profile_feed_slots' => null,
            'ncaa_profile_feed_slots' => ['performance_ncaa', 'approach_ncaa', 'adjustability_pitch'],
        ]);

        $this->artisan('app:remap-dataset-portals-by-name', ['--user' => (string) $user->id])
            ->assertSuccessful();

        $upload->refresh();
        $this->assertSame(DataSourceUpload::PORTAL_HS, $upload->dataset_portal);
        $this->assertNull($upload->ncaa_profile_feed_slots);
        $slots = DataSourceUpload::normalizeHsProfileFeedSlotList($upload->hs_profile_feed_slots);
        sort($slots);
        $this->assertSame(['adjustability_pitch', 'approach_overall', 'performance_overall'], $slots);
    }

    public function test_uppercase_tab_names_remap_correctly(): void
    {
        $user = User::factory()->create();
        $pathHs = 'data-source-uploads/remap-upper-hs-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathHs, "A\n1");
        $pathNcaa = 'data-source-uploads/remap-upper-ncaa-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathNcaa, "B\n2");

        $wrongHs = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_HS,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'NCAA - PITCH TYPES',
            'original_filename' => 'n.csv',
            'disk' => 'local',
            'path' => $pathNcaa,
            'header_row' => ['B'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['adjustability_pitch'],
        ]);
        $wrongNcaa = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'HS - OVERALL',
            'original_filename' => 'h.csv',
            'disk' => 'local',
            'path' => $pathHs,
            'header_row' => ['A'],
            'row_count' => 1,
            'ncaa_profile_feed_slots' => ['performance_ncaa'],
        ]);

        $this->artisan('app:remap-dataset-portals-by-name', ['--user' => (string) $user->id])
            ->assertSuccessful();

        $wrongHs->refresh();
        $wrongNcaa->refresh();
        $this->assertSame(DataSourceUpload::PORTAL_NCAA, $wrongHs->dataset_portal);
        $this->assertSame(DataSourceUpload::PORTAL_HS, $wrongNcaa->dataset_portal);
    }

    public function test_hs_data_index_auto_corrects_ncaa_pitch_types_stored_under_hs(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/auto-heal-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "P,PA\nFB,1");

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_HS,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'NCAA - PITCH TYPES',
            'original_filename' => 'p.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['P', 'PA'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['adjustability_pitch'],
        ]);

        $this->actingAs($user)->get(route('data-sources.index'))->assertOk();

        $fixed = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('name', 'NCAA - PITCH TYPES')
            ->first();
        $this->assertNotNull($fixed);
        $this->assertSame(DataSourceUpload::PORTAL_NCAA, $fixed->dataset_portal);
    }

    public function test_ncaa_data_index_auto_corrects_hs_overall_stored_under_ncaa(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/auto-heal-ncaa-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "PLAYER,YEAR\nA,1");

        DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_NCAA,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'HS - OVERALL',
            'original_filename' => 'o.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['PLAYER', 'YEAR'],
            'row_count' => 1,
            'ncaa_profile_feed_slots' => ['performance_ncaa'],
        ]);

        $this->actingAs($user)->get(route('ncaa-data-sources.index'))->assertOk();

        $fixed = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('name', 'HS - OVERALL')
            ->first();
        $this->assertNotNull($fixed);
        $this->assertSame(DataSourceUpload::PORTAL_HS, $fixed->dataset_portal);
    }

    public function test_dry_run_does_not_persist(): void
    {
        $user = User::factory()->create();
        $path = 'data-source-uploads/remap-dry-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($path, "A\n1");

        $upload = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'dataset_portal' => DataSourceUpload::PORTAL_HS,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'NCAA - Pitch Types',
            'original_filename' => 'p.csv',
            'disk' => 'local',
            'path' => $path,
            'header_row' => ['A'],
            'row_count' => 1,
            'hs_profile_feed_slots' => ['adjustability_pitch'],
        ]);

        $this->artisan('app:remap-dataset-portals-by-name', [
            '--user' => (string) $user->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $upload->refresh();
        $this->assertSame(DataSourceUpload::PORTAL_HS, $upload->dataset_portal);
    }
}
