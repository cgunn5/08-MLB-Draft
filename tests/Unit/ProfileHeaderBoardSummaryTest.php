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
            'board_type' => WorkingBoardEntry::BOARD_NCAA,
            'player_id' => $player->id,
            'round_key' => '2',
            'sort_order' => 0,
            'confidence' => '4',
            'risk' => '2',
        ]);

        $summary = ProfileHeaderBoardSummary::forPlayer($player, $user);

        $this->assertSame('5.5', $summary->roleDisplay);
        $this->assertSame('5.5', $summary->batGradeDisplay);
        $this->assertSame('2nd', $summary->targetRoundDisplay);
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
    public function profile_header_target_round_labels_use_ordinals_and_post_10(): void
    {
        $this->assertSame('1st', WorkingBoardEntry::profileHeaderTargetRoundLabel('1'));
        $this->assertSame('2nd', WorkingBoardEntry::profileHeaderTargetRoundLabel('2'));
        $this->assertSame('3rd', WorkingBoardEntry::profileHeaderTargetRoundLabel('3'));
        $this->assertSame('4th+', WorkingBoardEntry::profileHeaderTargetRoundLabel('4+'));
        $this->assertSame('Post-10', WorkingBoardEntry::profileHeaderTargetRoundLabel('10+'));
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
    public function risk_fill_style_uses_black_text_on_light_orange_background(): void
    {
        $this->assertSame(
            'background-color:#f6b283;color:#000000;font-weight:400;',
            WorkingBoardCellAppearance::riskFillStyle('2'),
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
