<?php

namespace Tests\Unit;

use App\Models\Player;
use App\Support\PlayerListRankCellHeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlayerListRankCellHeatTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function inline_style_matches_rank_distribution(): void
    {
        Player::factory()->create(['aggregate_rank' => 1]);
        Player::factory()->create(['aggregate_rank' => 10]);
        Player::factory()->create(['aggregate_rank' => null]);

        $style = PlayerListRankCellHeat::inlineStyle(5);
        $this->assertNotNull($style);
        $this->assertStringContainsString('background-color: rgb(', $style);
        $this->assertStringContainsString('color:', $style);
    }

    #[Test]
    public function inline_style_is_null_when_rank_missing_or_single_value_range(): void
    {
        $this->assertNull(PlayerListRankCellHeat::inlineStyle(null));

        Player::factory()->create(['aggregate_rank' => 7]);
        $this->assertNull(PlayerListRankCellHeat::inlineStyle(7));
    }

    #[Test]
    public function mdl_inline_style_matches_model_rank_distribution(): void
    {
        Player::factory()->create(['source_ranks' => ['model' => 2]]);
        Player::factory()->create(['source_ranks' => ['model' => 40]]);

        $style = PlayerListRankCellHeat::inlineStyleForModelDraftRank(15);
        $this->assertNotNull($style);
        $this->assertStringContainsString('background-color: rgb(', $style);
    }

    #[Test]
    public function mdl_inline_style_is_null_when_missing_or_flat_range(): void
    {
        $this->assertNull(PlayerListRankCellHeat::inlineStyleForModelDraftRank(null));

        Player::factory()->create(['source_ranks' => ['model' => 5]]);
        $this->assertNull(PlayerListRankCellHeat::inlineStyleForModelDraftRank(5));
    }
}
