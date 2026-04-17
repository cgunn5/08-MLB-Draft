<?php

namespace App\Support;

use App\Models\DataSourceUpload;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

final class HsRangerTraitsSheetResolver
{
    /**
     * @return array{
     *     has_source: bool,
     *     source_name: ?string,
     *     circuit_lonestar: ?array<string, string>,
     *     circuit_tusa: ?array<string, string>,
     *     circuit_pg: list<array<string, string>>,
     *     approach_lonestar: ?array<string, string>,
     *     impact: ?array<string, string>,
     *     impact_batted_ball: ?array<string, string>,
     *     adjust_ops_split: ?array<string, string>,
     *     adjust_pitch: list<array<string, string>>,
     *     cell_heat: array<string, array<string, string>|list<array<string, string>>>,
     * }
     */
    public function resolve(Player $player, User $user): array
    {
        $empty = $this->emptyPayload();

        if (trim($player->first_name) === '' && trim($player->last_name) === '') {
            return $empty;
        }

        $upload = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('for_hs_ranger_traits', true)
            ->first();

        if (! $upload instanceof DataSourceUpload) {
            return $empty;
        }

        $absolutePath = Storage::disk($upload->disk)->path($upload->path);
        if (! is_file($absolutePath)) {
            return $empty;
        }

        /** @var list<string> $headers */
        $headers = array_map(static fn ($h) => is_string($h) ? $h : '', $upload->header_row);
        if ($headers === []) {
            return $empty;
        }

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            return $empty;
        }

        try {
            $fileHeader = fgetcsv($handle);
            if ($fileHeader === false) {
                return $empty;
            }

            $playerCol = DataSourceCsvHeaders::playerColumnIndex($headers);
            $yearCol = DataSourceCsvHeaders::yearColumnIndex($headers);
            $pitchCol = DataSourceCsvHeaders::pitchColumnIndex($headers);

            [$slugToIdx, $slugToHeader] = $this->buildSlugMaps($headers);
            $matchedRows = [];

            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isBlankRow($row)) {
                    continue;
                }
                if (! $this->rowMatchesPlayer($row, $playerCol, $player)) {
                    continue;
                }
                $matchedRows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        if ($matchedRows === []) {
            return array_merge($empty, [
                'has_source' => true,
                'source_name' => $upload->name,
            ]);
        }

        $sorted = $this->sortRowsByYearDesc($matchedRows, $yearCol);
        $latest = $sorted[0] ?? null;

        $out = $empty;
        $out['has_source'] = true;
        $out['source_name'] = $upload->name;

        $heatRules = is_array($upload->heat_rules) ? $upload->heat_rules : null;
        $heatStats = is_array($upload->heat_column_stats) ? $upload->heat_column_stats : null;

        foreach (HsRangerTraitsSheetLayout::blocks() as $blockKey => $def) {
            $type = $def['type'];
            if ($type === 'single_year') {
                if ($latest === null) {
                    $out[$blockKey] = null;
                    $out['cell_heat'][$blockKey] = [];
                } else {
                    $includeYear = $blockKey !== 'adjust_ops_split';
                    $row = $this->extractSlugRow($latest, $headers, $def['slugs'], $slugToIdx, $yearCol, $includeYear);
                    $out[$blockKey] = $row;
                    $out['cell_heat'][$blockKey] = $this->heatForSlugs($row, $def['slugs'], $slugToHeader, $heatRules, $heatStats);
                }

                continue;
            }

            if ($type === 'multi_year') {
                $list = [];
                $heatList = [];
                foreach ($sorted as $r) {
                    $row = $this->extractSlugRow($r, $headers, $def['slugs'], $slugToIdx, $yearCol, true);
                    $list[] = $row;
                    $heatList[] = $this->heatForSlugs($row, $def['slugs'], $slugToHeader, $heatRules, $heatStats);
                }
                $out[$blockKey] = $list;
                $out['cell_heat'][$blockKey] = $heatList;

                continue;
            }

            if ($type === 'pitch_rows') {
                $defSlugs = $def['slugs'];
                if ($pitchCol === null) {
                    $pitchRows = [];
                    $heatPitch = [];
                    foreach ($def['pitches'] ?? [] as $pitchLabel) {
                        $pitchRows[] = [
                            'pitch' => (string) $pitchLabel,
                            ...$this->emptySlugCells($defSlugs),
                        ];
                        $heatPitch[] = [];
                    }
                    $out[$blockKey] = $pitchRows;
                    $out['cell_heat'][$blockKey] = $heatPitch;
                } else {
                    $wanted = array_map('strtoupper', $def['pitches'] ?? []);
                    $pitchRows = [];
                    $heatPitch = [];
                    foreach ($wanted as $pitchLabel) {
                        $found = null;
                        foreach ($sorted as $r) {
                            $pval = strtoupper(trim((string) ($r[$pitchCol] ?? '')));
                            if ($pval === $pitchLabel) {
                                $found = $r;

                                break;
                            }
                        }
                        if ($found !== null) {
                            $one = $this->extractSlugRow($found, $headers, $defSlugs, $slugToIdx, $yearCol, true);
                            $one['pitch'] = (string) ($found[$pitchCol] ?? $pitchLabel);
                            $pitchRows[] = $one;
                            $heatPitch[] = $this->heatForSlugs($one, $defSlugs, $slugToHeader, $heatRules, $heatStats);
                        } else {
                            $pitchRows[] = [
                                'pitch' => $pitchLabel,
                                ...$this->emptySlugCells($defSlugs),
                            ];
                            $heatPitch[] = [];
                        }
                    }
                    $out[$blockKey] = $pitchRows;
                    $out['cell_heat'][$blockKey] = $heatPitch;
                }
            }
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function emptySlugCells(array $slugs): array
    {
        $o = [];
        foreach ($slugs as $s) {
            $o[$s] = '—';
        }

        return $o;
    }

    /**
     * @return array{
     *     has_source: bool,
     *     source_name: ?string,
     *     circuit_lonestar: null,
     *     circuit_tusa: null,
     *     circuit_pg: list<array<string, string>>,
     *     approach_lonestar: null,
     *     impact: null,
     *     impact_batted_ball: null,
     *     adjust_ops_split: null,
     *     adjust_pitch: list<array<string, string>>,
     *     cell_heat: array<string, mixed>,
     * }
     */
    private function emptyPayload(): array
    {
        return [
            'has_source' => false,
            'source_name' => null,
            'circuit_lonestar' => null,
            'circuit_tusa' => null,
            'circuit_pg' => [],
            'approach_lonestar' => null,
            'impact' => null,
            'impact_batted_ball' => null,
            'adjust_ops_split' => null,
            'adjust_pitch' => [],
            'cell_heat' => $this->emptyCellHeatSkeleton(),
        ];
    }

    /**
     * @return array<string, array<string, string>|list<array<string, string>>>
     */
    private function emptyCellHeatSkeleton(): array
    {
        $h = [];
        foreach (array_keys(HsRangerTraitsSheetLayout::blocks()) as $bk) {
            $h[(string) $bk] = [];
        }

        return $h;
    }

    /**
     * @param  array<string, string>  $row
     * @param  list<string>  $slugs
     * @param  array<string, string>  $slugToHeader
     * @param  array<string, mixed>|null  $heatRules
     * @param  array<string, mixed>|null  $heatStats
     * @return array<string, string>
     */
    private function heatForSlugs(
        array $row,
        array $slugs,
        array $slugToHeader,
        ?array $heatRules,
        ?array $heatStats,
    ): array {
        if ($heatRules === null || $heatStats === null || $heatRules === [] || $heatStats === []) {
            return [];
        }

        $out = [];
        foreach ($slugs as $slug) {
            $val = $row[$slug] ?? '—';
            if ($val === '—') {
                continue;
            }
            $header = $slugToHeader[$slug] ?? '';
            if ($header === '') {
                continue;
            }
            $style = DataSourceCellHeat::inlineStyleFromRaw((string) $val, $header, $heatRules, $heatStats);
            if ($style !== null) {
                $out[$slug] = $style;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $headers
     * @return array{0: array<string, int>, 1: array<string, string>}
     */
    private function buildSlugMaps(array $headers): array
    {
        $map = [];
        foreach ($headers as $i => $h) {
            $slug = DataSourceCsvHeaders::slugify($h);
            if ($slug !== '') {
                $map[$slug] = (int) $i;
            }
        }

        $aliases = HsRangerTraitsSheetLayout::slugAliases();
        $resolvedIdx = [];
        $resolvedHeader = [];
        foreach ($aliases as $canonical => $aliasList) {
            foreach ($aliasList as $alias) {
                $key = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $alias));
                if ($key !== '' && isset($map[$key])) {
                    $idx = $map[$key];
                    $resolvedIdx[$canonical] = $idx;
                    $resolvedHeader[$canonical] = trim((string) ($headers[$idx] ?? ''));

                    break;
                }
            }
        }

        return [$resolvedIdx, $resolvedHeader];
    }

    /**
     * @param  list<string|null>  $row
     * @param  list<string>  $headers
     * @param  list<string>  $slugs
     * @param  array<string, int>  $slugToIdx
     * @return array<string, string>
     */
    private function extractSlugRow(array $row, array $headers, array $slugs, array $slugToIdx, ?int $yearCol, bool $includeYear = true): array
    {
        $out = [];
        if ($includeYear) {
            $yearVal = '—';
            if ($yearCol !== null && isset($row[$yearCol])) {
                $y = trim((string) $row[$yearCol]);
                if ($y !== '') {
                    $yearVal = $y;
                }
            }
            $out['year'] = $yearVal;
        }

        foreach ($slugs as $slug) {
            $idx = $slugToIdx[$slug] ?? null;
            $out[$slug] = $this->cellAt($row, $idx);
        }

        return $out;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function cellAt(array $row, ?int $idx): string
    {
        if ($idx === null || ! isset($row[$idx])) {
            return '—';
        }
        $t = trim((string) $row[$idx]);

        return $t !== '' ? $t : '—';
    }

    /**
     * @param  list<string|null>|null  $row
     */
    private function isBlankRow(?array $row): bool
    {
        if ($row === null || $row === []) {
            return true;
        }
        foreach ($row as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function rowMatchesPlayer(array $row, int $playerColIdx, Player $player): bool
    {
        $cell = strtoupper(preg_replace('/\s+/', ' ', trim((string) ($row[$playerColIdx] ?? ''))));
        if ($cell === '') {
            return false;
        }

        $last = strtoupper(trim((string) $player->last_name));
        $first = strtoupper(trim((string) $player->first_name));
        $c1 = $last.', '.$first;
        $c2 = $first.' '.$last;
        $c3 = $last.' '.$first;

        return $cell === $c1 || $cell === $c2 || $cell === $c3;
    }

    /**
     * @param  list<list<string|null>>  $rows
     * @return list<list<string|null>>
     */
    private function sortRowsByYearDesc(array $rows, ?int $yearCol): array
    {
        if ($yearCol === null) {
            return $rows;
        }

        usort($rows, function (array $a, array $b) use ($yearCol): int {
            $ya = (int) preg_replace('/\D/', '', (string) ($a[$yearCol] ?? ''));
            $yb = (int) preg_replace('/\D/', '', (string) ($b[$yearCol] ?? ''));

            return $yb <=> $ya;
        });

        return $rows;
    }
}
