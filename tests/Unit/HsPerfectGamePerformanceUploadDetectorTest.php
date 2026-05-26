<?php

namespace Tests\Unit;

use App\Support\HsPerfectGamePerformanceUploadDetector;
use PHPUnit\Framework\TestCase;

class HsPerfectGamePerformanceUploadDetectorTest extends TestCase
{
    public function test_detects_pg_style_header_with_iso(): void
    {
        $headers = ['PLAYER', 'YEAR', 'G', 'PA', 'AB', '1B', '2B', '3B', 'HR', 'BB', 'K', 'AVG', 'OBP', 'SLG', 'OPS', 'ISO', 'BB%', 'K%'];
        $this->assertTrue(HsPerfectGamePerformanceUploadDetector::headerRowLooksLikePgMultiYearCircuit($headers));
    }

    public function test_detects_pg_style_header_with_bb_k_pct_only(): void
    {
        $headers = ['PLAYER', 'YEAR', 'PA', 'AVG', 'OBP', 'SLG', 'OPS', 'BB%', 'K%'];
        $this->assertTrue(HsPerfectGamePerformanceUploadDetector::headerRowLooksLikePgMultiYearCircuit($headers));
    }

    public function test_rejects_counts_only_pg_sheet_without_iso_or_pct_pair(): void
    {
        $headers = ['PLAYER', 'YEAR', 'G', 'PA', 'AB', '1B', '2B', '3B', 'HR', 'BB', 'K'];
        $this->assertFalse(HsPerfectGamePerformanceUploadDetector::headerRowLooksLikePgMultiYearCircuit($headers));
    }

    public function test_rejects_without_year_column(): void
    {
        $headers = ['PLAYER', 'PA', 'AVG', 'OBP', 'SLG', 'OPS', 'ISO'];
        $this->assertFalse(HsPerfectGamePerformanceUploadDetector::headerRowLooksLikePgMultiYearCircuit($headers));
    }
}
