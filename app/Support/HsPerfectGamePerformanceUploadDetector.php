<?php

namespace App\Support;

/**
 * Detects yearly HS CSVs that match the Perfect Game / {@see HsRangerTraitsSheetLayout} `circuit_pg` profile slot.
 * Used to restore `performance_pg` assignments after DB resets when `header_row` survived on {@see DataSourceUpload}.
 */
final class HsPerfectGamePerformanceUploadDetector
{
    /**
     * @param  list<string|mixed>  $headerRow
     */
    public static function headerRowLooksLikePgMultiYearCircuit(array $headerRow): bool
    {
        /** @var list<string> $headers */
        $headers = array_map(static fn ($h) => is_string($h) ? $h : '', $headerRow);
        if ($headers === [] || DataSourceCsvHeaders::yearColumnIndex($headers) === null) {
            return false;
        }

        $slugs = [];
        foreach ($headers as $h) {
            $s = DataSourceCsvHeaders::slugify($h);
            if ($s !== '') {
                $slugs[$s] = true;
            }
        }

        foreach (['pa', 'ops', 'avg', 'obp', 'slg'] as $need) {
            if (! isset($slugs[$need])) {
                return false;
            }
        }

        if (isset($slugs['iso'])) {
            return true;
        }

        return isset($slugs['bbpct'], $slugs['kpct']);
    }
}
