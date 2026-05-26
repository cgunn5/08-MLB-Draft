<?php

namespace Tests\Feature;

use App\Models\DataSourceUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecoverHsPerfectGamePerformanceSlotTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private const HEADERS = ['PLAYER', 'YEAR', 'G', 'PA', 'AB', '1B', '2B', '3B', 'HR', 'BB', 'K', 'AVG', 'OBP', 'SLG', 'OPS', 'ISO', 'BB%', 'K%'];

    private function csvLine(string $player, string $year): string
    {
        return sprintf('"%s",%s,1,10,8,2,0,0,0,1,2,0.250,0.350,0.400,0.750,0.150,0.100,0.200', $player, $year);
    }

    public function test_prefers_largest_row_count_among_pg_shaped_uploads(): void
    {
        $user = User::factory()->admin()->create();
        $headerLine = implode(',', self::HEADERS);

        $pathSmall = 'data-source-uploads/pg-small-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathSmall, implode("\n", [
            $headerLine,
            $this->csvLine('SMALL, FIRST', '2024'),
        ]));
        $pathBig = 'data-source-uploads/pg-big-'.uniqid('', true).'.csv';
        $bigLines = [$headerLine];
        for ($i = 0; $i < 15; $i++) {
            $bigLines[] = $this->csvLine('BIG, PLAYER', (string) (2020 + $i));
        }
        Storage::disk('local')->put($pathBig, implode("\n", $bigLines));

        $small = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Small PG',
            'original_filename' => 'small.csv',
            'disk' => 'local',
            'path' => $pathSmall,
            'header_row' => self::HEADERS,
            'row_count' => 3,
            'hs_profile_feed_slots' => null,
        ]);
        $big = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Big PG',
            'original_filename' => 'big.csv',
            'disk' => 'local',
            'path' => $pathBig,
            'header_row' => self::HEADERS,
            'row_count' => 20,
            'hs_profile_feed_slots' => null,
        ]);

        $this->artisan('app:recover-hs-perfect-game-performance-slot', ['--user' => (string) $user->id])
            ->assertSuccessful();

        $small->refresh();
        $big->refresh();
        $this->assertNull($small->hs_profile_feed_slots);
        $this->assertSame(['performance_pg'], $big->hs_profile_feed_slots);
        $this->assertLessThan($big->id, $small->id);
    }

    public function test_reassign_moves_performance_pg_to_largest_pg_shaped_upload(): void
    {
        $user = User::factory()->admin()->create();
        $headerLine = implode(',', self::HEADERS);

        $pathSmall = 'data-source-uploads/pg-small-'.uniqid('', true).'.csv';
        Storage::disk('local')->put($pathSmall, implode("\n", [
            $headerLine,
            $this->csvLine('SMALL, FIRST', '2024'),
        ]));
        $pathBig = 'data-source-uploads/pg-big-'.uniqid('', true).'.csv';
        $bigLines = [$headerLine];
        for ($i = 0; $i < 15; $i++) {
            $bigLines[] = $this->csvLine('BIG, PLAYER', (string) (2020 + $i));
        }
        Storage::disk('local')->put($pathBig, implode("\n", $bigLines));

        $small = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Small PG',
            'original_filename' => 'small.csv',
            'disk' => 'local',
            'path' => $pathSmall,
            'header_row' => self::HEADERS,
            'row_count' => 3,
            'hs_profile_feed_slots' => ['performance_pg'],
        ]);
        $big = DataSourceUpload::query()->create([
            'user_id' => $user->id,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => 'Big PG',
            'original_filename' => 'big.csv',
            'disk' => 'local',
            'path' => $pathBig,
            'header_row' => self::HEADERS,
            'row_count' => 20,
            'hs_profile_feed_slots' => null,
        ]);

        $this->artisan('app:recover-hs-perfect-game-performance-slot', [
            '--user' => (string) $user->id,
            '--reassign' => true,
        ])->assertSuccessful();

        $small->refresh();
        $big->refresh();
        $this->assertNull($small->hs_profile_feed_slots);
        $this->assertSame(['performance_pg'], $big->hs_profile_feed_slots);
    }
}
