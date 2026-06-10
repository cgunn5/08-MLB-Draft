<?php

namespace Tests\Unit;

use App\Models\Player;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlayerProfileHeaderTest extends TestCase
{
    #[Test]
    public function ncaa_grade_row_definitions_use_k_zone_adj_and_platoon_labels(): void
    {
        $defs = Player::gradeRowDefinitionsNcaa();

        $this->assertSame(
            [
                'PERF' => 'grade_perf',
                'K-Zone' => 'grade_approach',
                'DAMAGE' => 'grade_damage',
                'Adj' => 'grade_adj',
                'Platoon' => 'grade_contact',
                'SWING' => 'grade_swing',
            ],
            $defs,
        );
    }

    #[Test]
    public function hs_grade_row_definitions_map_k_zone_and_adjustability_to_distinct_grades(): void
    {
        $defs = Player::gradeRowDefinitionsHs();

        $this->assertSame('grade_approach', $defs['K-Zone']);
        $this->assertSame('grade_contact', $defs['Adj']);
        $this->assertNotSame($defs['K-Zone'], $defs['Adj']);
    }

    #[Test]
    public function profile_header_bio_line_includes_school_and_position(): void
    {
        $player = new Player([
            'school' => 'Test High (CA)',
            'position' => 'SS',
            'bats' => 'R',
            'throws' => 'R',
            'age' => 17.5,
        ]);

        $this->assertSame(
            'Test High (CA) · SS',
            $player->profileHeaderBioLine(),
        );
    }

    #[Test]
    public function model_draft_list_rank_reads_source_ranks_model_key(): void
    {
        $player = new Player([
            'source_ranks' => ['model' => 42, 'mlb' => 50],
        ]);

        $this->assertSame(42, $player->modelDraftListRank());
    }

    #[Test]
    public function model_draft_list_rank_is_null_when_missing(): void
    {
        $player = new Player(['source_ranks' => ['mlb' => 1]]);

        $this->assertNull($player->modelDraftListRank());
    }
}
