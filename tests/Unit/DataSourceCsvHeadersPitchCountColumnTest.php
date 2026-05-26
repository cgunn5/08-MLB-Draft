<?php

namespace Tests\Unit;

use App\Support\DataSourceCsvHeaders;
use PHPUnit\Framework\TestCase;

class DataSourceCsvHeadersPitchCountColumnTest extends TestCase
{
    public function test_pitch_count_prefers_single_letter_p_when_present(): void
    {
        $headers = ['PLAYER', 'PA', 'P', 'PITCHES', 'OPS'];
        $this->assertSame(2, DataSourceCsvHeaders::pitchCountColumnIndex($headers));
    }

    public function test_pitch_count_finds_pitches_when_no_single_p(): void
    {
        $headers = ['PLAYER', 'RD', 'PITCH_TYPE', 'PITCHES', 'OPS'];
        $this->assertSame(3, DataSourceCsvHeaders::pitchCountColumnIndex($headers));
    }

    public function test_heat_volume_header_p_resolves_pitches_column(): void
    {
        $headers = ['PLAYER', 'PITCHES', 'OPS'];
        $browse = ['heat_volume_header' => 'P'];
        $this->assertSame(1, DataSourceCsvHeaders::heatVolumeColumnIndex($headers, $browse));
    }
}
