<?php

namespace Tests\Unit;

use App\Models\DataSourceUpload;
use App\Support\DataSourceCsvHeaders;
use PHPUnit\Framework\TestCase;

class DataSourceCsvHeadersGuessPortalTest extends TestCase
{
    public function test_guess_prefers_ncaa_when_xwoba_column_present_even_with_rnds(): void
    {
        $headers = ['PLAYER', 'YEAR', 'Rnds', 'PA', 'xWOBA', 'OPS'];
        $this->assertSame(DataSourceUpload::PORTAL_NCAA, DataSourceCsvHeaders::guessDatasetPortal($headers));
    }

    public function test_guess_hs_when_rnds_and_no_ncaa_indicator(): void
    {
        $headers = ['PLAYER', 'YEAR', 'Rnds', 'PA', 'OPS'];
        $this->assertSame(DataSourceUpload::PORTAL_HS, DataSourceCsvHeaders::guessDatasetPortal($headers));
    }
}
