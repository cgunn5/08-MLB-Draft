<?php

namespace Tests\Unit;

use App\Support\PlayerNoteFieldKeys;
use PHPUnit\Framework\TestCase;

class PlayerNoteFieldKeysTest extends TestCase
{
    public function test_sections_for_pool_normalizes_casing_and_whitespace(): void
    {
        $this->assertSame(
            PlayerNoteFieldKeys::sectionsForPool('ncaa'),
            PlayerNoteFieldKeys::sectionsForPool(' NCAA '),
        );
        $this->assertNotEmpty(PlayerNoteFieldKeys::sectionsForPool('HS'));
    }

    public function test_grade_attribute_uses_normalized_pool_for_pitch_coverage(): void
    {
        $this->assertSame(
            'grade_adj',
            PlayerNoteFieldKeys::gradeAttributeForNoteField('note_pitch_coverage', 'NCAA'),
        );
        $this->assertSame(
            'grade_contact',
            PlayerNoteFieldKeys::gradeAttributeForNoteField('note_pitch_coverage', 'hs'),
        );
    }
}
