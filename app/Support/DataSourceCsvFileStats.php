<?php

namespace App\Support;

final class DataSourceCsvFileStats
{
    /**
     * @return array{header_row: list<string>, row_count: int}
     */
    public static function read(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('unreadable');
        }

        try {
            $header = fgetcsv($handle);
            if ($header === false) {
                throw new \RuntimeException('empty');
            }

            /** @var list<string> $headerRow */
            $headerRow = array_map(static function ($c): string {
                if (! is_string($c)) {
                    return '';
                }
                $t = (string) preg_replace('/^\xEF\xBB\xBF|\x{FEFF}/u', '', trim($c));

                return $t;
            }, $header);
            if ($headerRow === [] || (count($headerRow) === 1 && $headerRow[0] === '')) {
                throw new \RuntimeException('no header');
            }

            $rowCount = 0;
            while (($row = fgetcsv($handle)) !== false) {
                if (self::isBlankCsvRow($row)) {
                    continue;
                }
                $rowCount++;
            }

            return [
                'header_row' => $headerRow,
                'row_count' => $rowCount,
            ];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string|null>|array<int, string|null>  $row
     */
    public static function isBlankCsvRow(array $row): bool
    {
        if ($row === [null]) {
            return true;
        }

        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
