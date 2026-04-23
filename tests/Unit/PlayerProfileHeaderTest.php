<?php

namespace Tests\Unit;

use App\Models\Player;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlayerProfileHeaderTest extends TestCase
{
    #[Test]
    public function profile_header_bio_line_includes_school_position_bats_throws_age(): void
    {
        $player = new Player([
            'school' => 'Test High (CA)',
            'position' => 'SS',
            'bats' => 'R',
            'throws' => 'R',
            'age' => 17.5,
        ]);

        $this->assertSame(
            'Test High (CA) · SS · B R · T R · AGE 17.5',
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
