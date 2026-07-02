<?php

namespace Tests\Unit;

use App\Models\Player;
use App\Models\User;
use App\Models\WorkingBoardEntry;
use App\Support\ProfileHeaderBoardSummary;
use App\Support\WorkingBoardCellAppearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileHeaderBoardSummaryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function summary_uses_board_entry_and_player_grades(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'grade_role' => 5.5,
            'grade_perf' => 6,
            'grade_approach' => 5,
            'grade_damage' => 6,
            'grade_adj' => 5,
            'grade_contact' => 6,
            'grade_swing' => 5,
        ]);
        WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'player_id' => $player->id,
            'round_key' => 'tweeners-3-targets',
            'sort_order' => 0,
            'confidence' => '4',
            'risk' => '2',
        ]);

        $summary = ProfileHeaderBoardSummary::forPlayer($player, $user);

        $this->assertSame('5.5', $summary->roleDisplay);
        $this->assertSame('5.5', $summary->batGradeDisplay);
        $this->assertSame('2nd-3rd', $summary->targetRoundDisplay);
        $this->assertSame('M-H', $summary->riskDisplay);
        $this->assertSame('4', $summary->confidenceDisplay);
    }

    #[Test]
    public function summary_uses_dashes_when_player_is_not_on_board(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'grade_role' => 4,
            'grade_perf' => 4,
            'grade_approach' => 4,
            'grade_contact' => 4,
            'grade_damage' => 4,
            'grade_swing' => 4,
        ]);

        $summary = ProfileHeaderBoardSummary::forPlayer($player, $user);

        $this->assertSame('4.0', $summary->roleDisplay);
        $this->assertSame('4.0', $summary->batGradeDisplay);
        $this->assertSame('-', $summary->targetRoundDisplay);
        $this->assertSame('-', $summary->riskDisplay);
        $this->assertSame('-', $summary->confidenceDisplay);
    }

    #[Test]
    public function summary_ignores_pool_board_when_player_is_only_on_hs_or_ncaa_board(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'grade_role' => 5.5,
            'grade_perf' => 6,
            'grade_approach' => 5,
            'grade_damage' => 6,
            'grade_adj' => 5,
            'grade_contact' => 6,
            'grade_swing' => 5,
        ]);
        WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_NCAA,
            'player_id' => $player->id,
            'round_key' => 'tweeners-3-targets',
            'sort_order' => 0,
            'confidence' => '4',
            'risk' => '2',
        ]);

        $summary = ProfileHeaderBoardSummary::forPlayer($player, $user);

        $this->assertSame('-', $summary->targetRoundDisplay);
        $this->assertSame('-', $summary->riskDisplay);
        $this->assertSame('-', $summary->confidenceDisplay);
    }

    #[Test]
    public function summary_prefers_master_board_over_pool_board(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'grade_role' => 5.5,
            'grade_perf' => 6,
            'grade_approach' => 5,
            'grade_damage' => 6,
            'grade_adj' => 5,
            'grade_contact' => 6,
            'grade_swing' => 5,
        ]);
        WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_NCAA,
            'player_id' => $player->id,
            'round_key' => 'tweeners-3-targets',
            'sort_order' => 0,
            'confidence' => '1',
            'risk' => '5',
        ]);
        WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'player_id' => $player->id,
            'round_key' => '1-targets',
            'sort_order' => 0,
            'confidence' => '4',
            'risk' => '2',
        ]);

        $summary = ProfileHeaderBoardSummary::forPlayer($player, $user);

        $this->assertSame('1st', $summary->targetRoundDisplay);
        $this->assertSame('M-H', $summary->riskDisplay);
        $this->assertSame('4', $summary->confidenceDisplay);
    }

    #[Test]
    public function profile_header_target_round_labels_use_bucket_labels(): void
    {
        $this->assertSame('1st', WorkingBoardEntry::profileHeaderTargetRoundLabel('1-targets'));
        $this->assertSame('1st / Pass', WorkingBoardEntry::profileHeaderTargetRoundLabel('1-pass'));
        $this->assertSame('2nd-3rd', WorkingBoardEntry::profileHeaderTargetRoundLabel('tweeners-3-targets'));
        $this->assertSame('4th-5th / Pass', WorkingBoardEntry::profileHeaderTargetRoundLabel('4-5-pass'));
        $this->assertSame('6th+', WorkingBoardEntry::profileHeaderTargetRoundLabel('6-plus-targets'));
        $this->assertSame('6th+ / Pass', WorkingBoardEntry::profileHeaderTargetRoundLabel('6-plus-pass'));
        $this->assertSame('2nd-3rd', WorkingBoardEntry::profileHeaderTargetRoundLabel('2'));
        $this->assertSame('4th-5th', WorkingBoardEntry::profileHeaderTargetRoundLabel('5-7'));
        $this->assertSame('6th+', WorkingBoardEntry::profileHeaderTargetRoundLabel('post-10'));
    }

    #[Test]
    public function summary_shows_pass_status_for_players_in_pass_columns(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['player_pool' => 'hs']);
        WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'player_id' => $player->id,
            'round_key' => 'tweeners-3-pass',
            'sort_order' => 0,
            'confidence' => '3',
            'risk' => '4',
        ]);

        $summary = ProfileHeaderBoardSummary::forPlayer($player, $user);

        $this->assertSame('2nd-3rd / Pass', $summary->targetRoundDisplay);
    }

    #[Test]
    public function confidence_fill_style_uses_black_text_on_light_green_background(): void
    {
        $this->assertSame(
            'background-color:#b8d68c;color:#000000;font-weight:400;',
            WorkingBoardCellAppearance::confidenceFillStyle('4'),
        );
    }

    #[Test]
    public function confidence_fill_style_uses_black_text_on_yellow_background(): void
    {
        $this->assertSame(
            'background-color:#FEE69C;color:#000000;font-weight:400;',
            WorkingBoardCellAppearance::confidenceFillStyle('3'),
        );
    }

    #[Test]
    public function confidence_fill_style_uses_black_text_on_white_background(): void
    {
        $this->assertSame(
            'background-color:#ffffff;color:#000000;font-weight:400;',
            WorkingBoardCellAppearance::confidenceFillStyle(''),
        );
    }

    #[Test]
    public function risk_fill_style_matches_confidence_scale_colors(): void
    {
        $this->assertSame(
            WorkingBoardCellAppearance::confidenceFillStyle('2'),
            WorkingBoardCellAppearance::riskFillStyle('2'),
        );
        $this->assertSame(
            WorkingBoardCellAppearance::confidenceFillStyle('5'),
            WorkingBoardCellAppearance::riskFillStyle('5'),
        );
    }

    #[Test]
    public function profile_header_role_chip_uses_grade_scale_palette(): void
    {
        $this->assertSame(
            'background-color:#FBD8DB;color:#000000;font-weight:400;',
            ProfileHeaderBoardSummary::forPlayer(
                Player::factory()->create(['grade_role' => 5.5]),
                User::factory()->create(),
            )->roleCellStyle,
        );
    }
}
