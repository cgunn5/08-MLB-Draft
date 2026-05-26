<?php

namespace Tests\Unit;

use App\Models\DataSourceUpload;
use App\Support\DataSourcePortalMisplacementFixer;
use PHPUnit\Framework\TestCase;

class DataSourcePortalMisplacementFixerTest extends TestCase
{
    public function test_infer_ncaa_pitch_types_with_all_caps(): void
    {
        $this->assertSame(
            DataSourceUpload::PORTAL_NCAA,
            DataSourcePortalMisplacementFixer::inferCorrectPortalForUploadName('NCAA - PITCH TYPES')
        );
    }

    public function test_infer_hs_overall_with_all_caps(): void
    {
        $this->assertSame(
            DataSourceUpload::PORTAL_HS,
            DataSourcePortalMisplacementFixer::inferCorrectPortalForUploadName('HS - OVERALL')
        );
    }

    public function test_does_not_treat_perfect_game_as_hs_overall(): void
    {
        $this->assertNull(
            DataSourcePortalMisplacementFixer::inferCorrectPortalForUploadName('HS STATS - PERFECT GAME CAREER')
        );
    }

    public function test_does_not_move_ncaa_overall(): void
    {
        $this->assertNull(
            DataSourcePortalMisplacementFixer::inferCorrectPortalForUploadName('NCAA - OVERALL')
        );
    }
}
