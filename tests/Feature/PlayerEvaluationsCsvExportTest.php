<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use App\Models\WorkingBoardEntry;
use App\Support\PlayerEvaluationsCsvExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerEvaluationsCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_download_evaluations_csv(): void
    {
        $this->get(route('notes.export'))->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_download_evaluations_csv(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notes.export'))
            ->assertForbidden();
    }

    public function test_admin_can_download_evaluations_csv(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'grade_role' => 5,
            'grade_perf' => 6,
            'grade_approach' => 5.5,
            'grade_damage' => 6,
            'grade_adj' => 4,
            'grade_contact' => 5,
            'grade_swing' => 6,
            'master_take' => 'Top-5 round talent',
            'note_performance' => 'Strong bat speed',
            'note_approach_miss' => 'Elite zone control',
            'note_engine' => 'Plus raw power',
            'note_pitch_coverage' => 'Handles velo',
            'note_left_right' => 'Crushes RHP',
            'note_swing' => 'Compact path',
        ]);

        WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_NCAA,
            'player_id' => $player->id,
            'round_key' => '2',
            'sort_order' => 0,
            'confidence' => '4',
            'risk' => '2',
        ]);

        $response = $this->actingAs($user)->get(route('notes.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $lines = preg_split('/\r\n|\n|\r/', trim($response->streamedContent()));
        $this->assertNotFalse($lines);
        $this->assertCount(2, $lines);

        $headers = str_getcsv($lines[0]);
        $this->assertSame(PlayerEvaluationsCsvExporter::columnHeaders(), $headers);

        $row = str_getcsv($lines[1]);
        $this->assertSame('DOE, JOHN', $row[0]);
        $this->assertSame('2', $row[1]);
        $this->assertSame('5', $row[2]);
        $this->assertSame('4', $row[3]);
        $this->assertSame('M-H', $row[4]);
        $this->assertSame('6', $row[5]);
        $this->assertSame('5.5', $row[6]);
        $this->assertSame('6', $row[7]);
        $this->assertSame('4', $row[8]);
        $this->assertSame('5', $row[9]);
        $this->assertSame('6', $row[10]);
        $this->assertSame('Top-5 round talent', $row[11]);
        $this->assertSame('Strong bat speed', $row[12]);
        $this->assertSame('Elite zone control', $row[13]);
        $this->assertSame('Plus raw power', $row[14]);
        $this->assertSame('Handles velo', $row[15]);
        $this->assertSame('Crushes RHP', $row[16]);
        $this->assertSame('Compact path', $row[17]);
    }

    public function test_hs_player_leaves_platoon_columns_blank(): void
    {
        $user = User::factory()->admin()->create();
        Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Sam',
            'last_name' => 'Smith',
            'grade_contact' => 5,
            'grade_adj' => 7,
            'note_left_right' => 'Should not export',
            'note_pitch_coverage' => 'Good adjustability',
        ]);

        $response = $this->actingAs($user)->get(route('notes.export'));
        $lines = preg_split('/\r\n|\n|\r/', trim($response->streamedContent()));
        $row = str_getcsv($lines[1]);

        $this->assertSame('5', $row[8]);
        $this->assertSame('', $row[9]);
        $this->assertSame('Good adjustability', $row[15]);
        $this->assertSame('', $row[16]);
    }
}
