<?php

namespace App\Support;

use App\Models\DataSourceUpload;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

final class HsRangerTraitsSheetResolver
{
    /** Blocks whose heat uses the HS Stats comp bucket when {@see HsCompHeatScope} is set. */
    private const BLOCK_KEYS_HS_COMP_SCOPED_HEAT = [
        'circuit_lonestar',
        'approach_lonestar',
        'impact',
        'impact_batted_ball',
        'adjust_ops_split',
        'adjust_pitch',
    ];

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
     *     overall_demographics: ?array{bats: string, throws: string, age: string},
     *     radar: ?array{axes: list<array<string, mixed>>, values: list<float>, comp_scope: string|null},
     * }
     */
    public function resolve(Player $player, User $user, ?string $compHeatRaw = null): array
    {
        $compHeatScope = HsCompHeatScope::normalize($compHeatRaw);
        $empty = $this->emptyPayload();

        if (trim($player->first_name) === '' && trim($player->last_name) === '') {
            return $empty;
        }

        /** @var list<DataSourceUpload> $assigned */
        $assigned = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->filter(static function (DataSourceUpload $upload): bool {
                $slots = $upload->hs_profile_feed_slots;

                return is_array($slots) && $slots !== [];
            })
            ->values()
            ->all();

        if ($assigned === []) {
            return $empty;
        }

        $sourceNames = [];
        $anyHadRows = false;
        $out = $this->emptyPayload();

        foreach ($assigned as $upload) {
            $slots = $upload->hs_profile_feed_slots;
            if (! is_array($slots)) {
                continue;
            }
            $blockSet = [];
            foreach ($slots as $slot) {
                if (! is_string($slot)) {
                    continue;
                }
                foreach (HsRangerTraitsSheetLayout::blockKeysForProfileSlot($slot) as $bk) {
                    $blockSet[$bk] = true;
                }
            }
            $blockKeys = array_keys($blockSet);
            if ($blockKeys === []) {
                continue;
            }
            $sourceNames[] = $upload->name;
            $m = $this->materializeBlocksFromUpload($upload, $player, $blockKeys, $compHeatScope);
            if ($m['had_player_rows']) {
                $anyHadRows = true;
            }
            $this->mergeMaterialized($out, $m);
        }

        $label = $sourceNames === []
            ? null
            : implode(' · ', array_values(array_unique($sourceNames)));

        if (! $anyHadRows) {
            return array_merge($empty, [
                'has_source' => true,
                'source_name' => $label,
            ]);
        }

        $out['has_source'] = true;
        $out['source_name'] = $label;
        $out['radar'] = $this->buildHsOverallRadarPayload($assigned, $player, $compHeatScope);

        return $out;
    }

    /**
     * @param  list<string>  $blockKeys
     * @return array{had_player_rows: bool, partial: array<string, mixed>, partial_heat: array<string, mixed>, demographics: ?array{bats: string, throws: string, age: string}}
     */
    private function materializeBlocksFromUpload(
        DataSourceUpload $upload,
        Player $player,
        array $blockKeys,
        ?string $compHeatScope = null,
    ): array {
        $allowed = array_fill_keys($blockKeys, true);
        $emptyResult = [
            'had_player_rows' => false,
            'partial' => [],
            'partial_heat' => [],
            'demographics' => null,
        ];

        $absolutePath = Storage::disk($upload->disk)->path($upload->path);
        if (! is_file($absolutePath)) {
            return $emptyResult;
        }

        /** @var list<string> $headers */
        $headers = array_map(static fn ($h) => is_string($h) ? $h : '', $upload->header_row);
        if ($headers === []) {
            return $emptyResult;
        }

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            return $emptyResult;
        }

        try {
            $fileHeader = fgetcsv($handle);
            if ($fileHeader === false) {
                return $emptyResult;
            }

            $playerCol = DataSourceCsvHeaders::playerColumnIndex($headers);
            $yearCol = DataSourceCsvHeaders::yearColumnIndex($headers);
            $pitchCol = DataSourceCsvHeaders::pitchColumnIndex($headers);

            [$slugToIdx, $slugToHeader] = $this->buildSlugMaps($headers);
            $matchedRows = [];
            /** @var list<list<string|null>> $allDataRows */
            $allDataRows = [];
            $carryPlayerMatches = false;

            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isBlankRow($row)) {
                    continue;
                }
                $allDataRows[] = $row;
                $playerCell = trim((string) ($row[$playerCol] ?? ''));
                if ($playerCell !== '') {
                    $carryPlayerMatches = $this->rowMatchesPlayer($row, $playerCol, $player);
                }
                if ($carryPlayerMatches) {
                    $matchedRows[] = $row;
                }
            }
        } finally {
            fclose($handle);
        }

        if ($matchedRows === []) {
            return $emptyResult;
        }

        $sorted = $this->sortRowsByYearDesc($matchedRows, $yearCol);
        $latest = $sorted[0] ?? null;
        $latestAggregate = $this->rowForAggregateStats($sorted, $pitchCol, $yearCol);

        $heatRules = is_array($upload->heat_rules) ? $upload->heat_rules : null;
        $heatStats = is_array($upload->heat_column_stats) ? $upload->heat_column_stats : null;
        $heatMinPaYearly = $this->heatMinPaFromBrowse(is_array($upload->dataset_browse_settings) ? $upload->dataset_browse_settings : null);

        $compCol = DataSourceCsvHeaders::hsCompBucketColumnIndex($headers);
        $rowsForCompHeatStats = $allDataRows;
        if ($compHeatScope !== null && $compCol !== null) {
            $rowsForCompHeatStats = [];
            foreach ($allDataRows as $r) {
                $cell = trim((string) ($r[$compCol] ?? ''));
                if (strcasecmp($cell, $compHeatScope) === 0) {
                    $rowsForCompHeatStats[] = $r;
                }
            }
        }
        $scopedHeatStats = null;
        if ($compHeatScope !== null
            && $compCol !== null
            && is_array($heatRules)
            && $heatRules !== []) {
            $heatBrowse = is_array($upload->dataset_browse_settings) ? $upload->dataset_browse_settings : null;
            $volIdxScoped = DataSourceCsvHeaders::heatVolumeColumnIndex($headers, $heatBrowse);
            $volMinScoped = $heatMinPaYearly;
            if ($volMinScoped !== null && $volIdxScoped === null) {
                $volMinScoped = null;
            }
            $computedScoped = DataSourceHeatColumnStats::compute($headers, $rowsForCompHeatStats, $heatRules, $volIdxScoped, $volMinScoped);
            $scopedHeatStats = $computedScoped !== [] ? $computedScoped : null;
        }

        $partial = [];
        $partialHeat = [];
        $demographics = null;

        foreach (HsRangerTraitsSheetLayout::blocks() as $blockKey => $def) {
            if (! isset($allowed[$blockKey])) {
                continue;
            }
            $type = $def['type'];
            if ($type === 'single_year') {
                $sourceRow = $latestAggregate ?? $latest;
                if ($blockKey === 'circuit_lonestar') {
                    if ($sourceRow !== null) {
                        $demCells = $this->extractSlugRow($sourceRow, $headers, ['bats', 'throws', 'demo_age'], $slugToIdx, $yearCol, false);
                        $demographics = [
                            'bats' => $demCells['bats'] ?? PlayerSheetPlaceholder::CELL,
                            'throws' => $demCells['throws'] ?? PlayerSheetPlaceholder::CELL,
                            'age' => $this->formatOverallDemographicAge($demCells['demo_age'] ?? PlayerSheetPlaceholder::CELL),
                        ];
                    } else {
                        $demographics = [
                            'bats' => PlayerSheetPlaceholder::CELL,
                            'throws' => PlayerSheetPlaceholder::CELL,
                            'age' => PlayerSheetPlaceholder::CELL,
                        ];
                    }
                }
                if ($sourceRow === null) {
                    $partial[$blockKey] = null;
                    $partialHeat[$blockKey] = [];
                } else {
                    $includeYear = $blockKey !== 'adjust_ops_split';
                    $row = $this->extractSlugRow($sourceRow, $headers, $def['slugs'], $slugToIdx, $yearCol, $includeYear);
                    $partial[$blockKey] = $row;
                    $partialHeat[$blockKey] = $this->heatForSlugs(
                        $row,
                        $def['slugs'],
                        $slugToHeader,
                        $heatRules,
                        $this->heatStatsForBlock($blockKey, $compHeatScope, $compCol, $scopedHeatStats, $heatStats),
                    );
                }

                continue;
            }

            if ($type === 'multi_year') {
                $list = [];
                $heatList = [];
                foreach ($sorted as $r) {
                    $rowRaw = $this->extractSlugRow($r, $headers, $def['slugs'], $slugToIdx, $yearCol, true);
                    $qualHeat = $blockKey === 'circuit_pg'
                        ? $this->slugRowMeetsHeatPaMinimum($heatMinPaYearly, $rowRaw)
                        : true;
                    $heatList[] = $this->heatForSlugs(
                        $rowRaw,
                        $def['slugs'],
                        $slugToHeader,
                        $heatRules,
                        $this->heatStatsForBlock($blockKey, $compHeatScope, $compCol, $scopedHeatStats, $heatStats),
                        null,
                        $qualHeat,
                    );
                    if ($blockKey === 'circuit_pg') {
                        $list[] = $this->formatCircuitPgRowForDisplay($rowRaw);
                    } else {
                        $list[] = $rowRaw;
                    }
                }
                if ($blockKey === 'circuit_pg') {
                    $careerMaster = DataSourceUpload::query()
                        ->where('user_id', $upload->user_id)
                        ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)
                        ->where('career_pg_source_upload_id', $upload->id)
                        ->first();

                    $useCareerMasterHeat = $careerMaster !== null
                        && is_array($careerMaster->heat_rules) && $careerMaster->heat_rules !== []
                        && is_array($careerMaster->heat_column_stats) && $careerMaster->heat_column_stats !== [];

                    $careerPack = CareerPgStatsAggregator::profileCircuitPgCareerWithHeatRaw($sorted, $headers, $pitchCol);
                    if ($careerPack !== null) {
                        $careerRowDisplay = $this->formatCircuitPgRowForDisplay($careerPack['row']);
                        $heatMinPaCareer = $useCareerMasterHeat
                            ? $this->heatMinPaFromBrowse(is_array($careerMaster->dataset_browse_settings) ? $careerMaster->dataset_browse_settings : null)
                            : $heatMinPaYearly;
                        $careerQualHeat = $this->slugRowMeetsHeatPaMinimum($heatMinPaCareer, $careerRowDisplay);
                        if ($useCareerMasterHeat) {
                            $careerHeat = $this->heatForSlugs(
                                $careerRowDisplay,
                                $def['slugs'],
                                $slugToHeader,
                                $careerMaster->heat_rules,
                                $careerMaster->heat_column_stats,
                                null,
                                $careerQualHeat
                            );
                        } else {
                            $careerHeat = $this->heatForSlugs(
                                $careerRowDisplay,
                                $def['slugs'],
                                $slugToHeader,
                                $heatRules,
                                $heatStats,
                                $careerPack['heat_raw_by_slug'],
                                $careerQualHeat
                            );
                        }
                        array_unshift($list, $careerRowDisplay);
                        array_unshift($heatList, $careerHeat);
                    }
                }
                $partial[$blockKey] = $list;
                $partialHeat[$blockKey] = $heatList;

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
                    $partial[$blockKey] = $pitchRows;
                    $partialHeat[$blockKey] = $heatPitch;
                } else {
                    $wanted = array_map('strtoupper', $def['pitches'] ?? []);
                    $pitchRows = [];
                    $heatPitch = [];
                    $rulesForHeat = is_array($heatRules) && $heatRules !== [] ? $heatRules : null;
                    foreach ($wanted as $pitchLabel) {
                        $groupRows = [];
                        foreach ($allDataRows as $r) {
                            if ($compHeatScope !== null && $compCol !== null) {
                                $cbc = trim((string) ($r[$compCol] ?? ''));
                                if (strcasecmp($cbc, $compHeatScope) !== 0) {
                                    continue;
                                }
                            }
                            if ($this->pitchBucket($pitchCol, $r) === $pitchLabel) {
                                $groupRows[] = $r;
                            }
                        }
                        $heatBrowse = is_array($upload->dataset_browse_settings) ? $upload->dataset_browse_settings : null;
                        $volumeIdxPitch = DataSourceCsvHeaders::heatVolumeColumnIndex($headers, $heatBrowse);
                        $volumeMinPitch = $heatMinPaYearly;
                        if ($volumeMinPitch !== null && $volumeIdxPitch === null) {
                            $volumeMinPitch = null;
                        }
                        $groupHeatStats = ($rulesForHeat !== null)
                            ? DataSourceHeatColumnStats::compute($headers, $groupRows, $rulesForHeat, $volumeIdxPitch, $volumeMinPitch)
                            : null;
                        $found = null;
                        foreach ($sorted as $r) {
                            if ($this->pitchBucket($pitchCol, $r) === $pitchLabel) {
                                $found = $r;

                                break;
                            }
                        }
                        if ($found !== null) {
                            $one = $this->extractSlugRow($found, $headers, $defSlugs, $slugToIdx, $yearCol, true);
                            $one['pitch'] = $pitchLabel;
                            $pitchRows[] = $one;
                            $pitchQualHeat = true;
                            if ($volumeMinPitch !== null && $volumeIdxPitch !== null) {
                                $pitchQualHeat = $this->csvNumericCellMeetsMinimum($volumeMinPitch, $found, $volumeIdxPitch);
                            }
                            $heatPitch[] = $this->heatForSlugs(
                                $one,
                                $defSlugs,
                                $slugToHeader,
                                $heatRules,
                                $groupHeatStats ?? $this->heatStatsForBlock('adjust_pitch', $compHeatScope, $compCol, $scopedHeatStats, $heatStats),
                                null,
                                $pitchQualHeat,
                            );
                        } else {
                            $pitchRows[] = [
                                'pitch' => $pitchLabel,
                                ...$this->emptySlugCells($defSlugs),
                            ];
                            $heatPitch[] = [];
                        }
                    }
                    $partial[$blockKey] = $pitchRows;
                    $partialHeat[$blockKey] = $heatPitch;
                }
            }
        }

        return [
            'had_player_rows' => true,
            'partial' => $partial,
            'partial_heat' => $partialHeat,
            'demographics' => $demographics,
        ];
    }

    /**
     * @param  array<string, mixed>  $out
     * @param  array{had_player_rows: bool, partial: array<string, mixed>, partial_heat: array<string, mixed>, demographics: ?array{bats: string, throws: string, age: string}}  $material
     */
    private function mergeMaterialized(array &$out, array $material): void
    {
        foreach ($material['partial'] as $key => $value) {
            $out[$key] = $value;
        }
        foreach ($material['partial_heat'] as $key => $value) {
            $out['cell_heat'][$key] = $value;
        }
        if ($material['demographics'] !== null) {
            $out['overall_demographics'] = $material['demographics'];
        }
    }

    private function formatOverallDemographicAge(string $cell): string
    {
        if (PlayerSheetPlaceholder::isEmptyDisplay($cell)) {
            return PlayerSheetPlaceholder::CELL;
        }
        $norm = str_replace(',', '.', trim($cell));
        if (is_numeric($norm)) {
            $f = (float) $norm;
            $s = rtrim(rtrim(number_format($f, 2, '.', ''), '0'), '.');

            return $s !== '' ? $s : '0';
        }

        return $cell;
    }

    /**
     * @return array<string, string>
     */
    private function emptySlugCells(array $slugs): array
    {
        $o = [];
        foreach ($slugs as $s) {
            $o[$s] = PlayerSheetPlaceholder::CELL;
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
     *     overall_demographics: ?array{bats: string, throws: string, age: string},
     *     radar: null,
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
            'overall_demographics' => null,
            'radar' => null,
        ];
    }

    /**
     * @param  list<DataSourceUpload>  $assigned
     * @return array{axes: list<array<string, mixed>>, values: list<float>, comp_scope: string|null}|null
     */
    private function buildHsOverallRadarPayload(array $assigned, Player $player, ?string $compHeatScope): ?array
    {
        foreach ($assigned as $upload) {
            $radar = $this->tryRadarPayloadFromUpload($upload, $player, $compHeatScope);
            if ($radar !== null) {
                return $radar;
            }
        }

        return null;
    }

    /**
     * @return array{axes: list<array<string, mixed>>, values: list<float>, comp_scope: string|null}|null
     */
    private function tryRadarPayloadFromUpload(DataSourceUpload $upload, Player $player, ?string $compHeatScope): ?array
    {
        $slots = $upload->hs_profile_feed_slots;
        if (! is_array($slots) || $slots === []) {
            return null;
        }

        $absolutePath = Storage::disk($upload->disk)->path($upload->path);
        if (! is_file($absolutePath)) {
            return null;
        }

        /** @var list<string> $headers */
        $headers = array_map(static fn ($h) => is_string($h) ? $h : '', $upload->header_row);
        if ($headers === []) {
            return null;
        }

        $radarSlugs = array_column(HsOverallRadarNtile::AXES, 'slug');

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            return null;
        }

        try {
            $fileHeader = fgetcsv($handle);
            if ($fileHeader === false) {
                return null;
            }

            $playerCol = DataSourceCsvHeaders::playerColumnIndex($headers);
            $yearCol = DataSourceCsvHeaders::yearColumnIndex($headers);
            $pitchCol = DataSourceCsvHeaders::pitchColumnIndex($headers);

            [$slugToIdx, $_slugToHeader] = $this->buildSlugMaps($headers);
            foreach ($radarSlugs as $slug) {
                if (! isset($slugToIdx[$slug])) {
                    return null;
                }
            }

            $compCol = DataSourceCsvHeaders::hsCompBucketColumnIndex($headers);
            if ($compHeatScope !== null && $compCol === null) {
                return null;
            }

            /** @var list<list<string|null>> $allDataRows */
            $allDataRows = [];
            $matchedRows = [];
            $carryPlayerMatches = false;

            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isBlankRow($row)) {
                    continue;
                }
                $allDataRows[] = $row;
                $playerCell = trim((string) ($row[$playerCol] ?? ''));
                if ($playerCell !== '') {
                    $carryPlayerMatches = $this->rowMatchesPlayer($row, $playerCol, $player);
                }
                if ($carryPlayerMatches) {
                    $matchedRows[] = $row;
                }
            }
        } finally {
            fclose($handle);
        }

        if ($matchedRows === []) {
            return null;
        }

        // When the sheet uses a single aggregate row (blank / non-matching Rnds), comp-scoped
        // heat still uses the bucket population; keep the same player row for radar instead of
        // dropping the chart when no row matches the active comp tag.
        if ($compHeatScope !== null && $compCol !== null) {
            $matchedRowsScoped = array_values(array_filter(
                $matchedRows,
                static function (array $r) use ($compCol, $compHeatScope): bool {
                    return strcasecmp(trim((string) ($r[$compCol] ?? '')), $compHeatScope) === 0;
                },
            ));
            if ($matchedRowsScoped !== []) {
                $matchedRows = $matchedRowsScoped;
            }
        }

        $rowsForRadar = $allDataRows;
        if ($compHeatScope !== null && $compCol !== null) {
            $rowsForRadar = [];
            foreach ($allDataRows as $r) {
                $cell = trim((string) ($r[$compCol] ?? ''));
                if (strcasecmp($cell, $compHeatScope) === 0) {
                    $rowsForRadar[] = $r;
                }
            }
            if ($rowsForRadar === []) {
                return null;
            }
        }

        $heatMinPa = $this->heatMinPaFromBrowse(is_array($upload->dataset_browse_settings) ? $upload->dataset_browse_settings : null);
        $paIdx = DataSourceCsvHeaders::plateAppearancesColumnIndex($headers);
        if ($heatMinPa !== null && $paIdx === null) {
            $heatMinPa = null;
        }

        $sorted = $this->sortRowsByYearDesc($matchedRows, $yearCol);
        $sourceRow = $this->rowForAggregateStats($sorted, $pitchCol, $yearCol);
        if ($sourceRow === null) {
            return null;
        }

        $playerSlugRow = $this->extractSlugRow($sourceRow, $headers, $radarSlugs, $slugToIdx, $yearCol, false);

        return HsOverallRadarNtile::compute(
            $headers,
            $rowsForRadar,
            $slugToIdx,
            $playerSlugRow,
            $paIdx,
            $heatMinPa,
            $compHeatScope,
        );
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
     * @param  array<string, mixed>|null  $scopedHeatStats
     * @param  array<string, mixed>|null  $heatStats
     * @return array<string, mixed>|null
     */
    private function heatStatsForBlock(
        string $blockKey,
        ?string $compHeatScope,
        ?int $compCol,
        ?array $scopedHeatStats,
        ?array $heatStats,
    ): ?array {
        $use = $compHeatScope !== null
            && $compCol !== null
            && in_array($blockKey, self::BLOCK_KEYS_HS_COMP_SCOPED_HEAT, true)
            && is_array($scopedHeatStats)
            && $scopedHeatStats !== [];

        return $use ? $scopedHeatStats : $heatStats;
    }

    /**
     * @param  array<string, string>  $row
     * @param  list<string>  $slugs
     * @param  array<string, string>  $slugToHeader
     * @param  array<string, mixed>|null  $heatRules
     * @param  array<string, mixed>|null  $heatStats
     * @param  array<string, string>|null  $heatRawBySlug  Raw cell text for heat only (e.g. decimal BB% while row shows "20.0%")
     * @return array<string, string>
     */
    private function heatForSlugs(
        array $row,
        array $slugs,
        array $slugToHeader,
        ?array $heatRules,
        ?array $heatStats,
        ?array $heatRawBySlug = null,
        bool $rowQualifiesForHeat = true,
    ): array {
        if (! $rowQualifiesForHeat) {
            return [];
        }
        if ($heatRules === null || $heatStats === null || $heatRules === [] || $heatStats === []) {
            return [];
        }

        $out = [];
        foreach ($slugs as $slug) {
            $val = $row[$slug] ?? PlayerSheetPlaceholder::CELL;
            if ($heatRawBySlug !== null && array_key_exists($slug, $heatRawBySlug)) {
                $val = $heatRawBySlug[$slug];
            }
            if (PlayerSheetPlaceholder::isEmptyDisplay((string) $val)) {
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
     * @param  array<string, mixed>|null  $browse
     */
    private function heatMinPaFromBrowse(?array $browse): ?float
    {
        if (! is_array($browse) || ! array_key_exists('heat_min_pa', $browse)) {
            return null;
        }
        $v = $browse['heat_min_pa'];
        if ($v === null || $v === '' || ! is_numeric($v)) {
            return null;
        }
        $f = (float) $v;

        return $f >= 0 ? $f : null;
    }

    /**
     * @param  array<string, string>  $slugRow
     */
    private function slugRowMeetsHeatPaMinimum(?float $minPa, array $slugRow): bool
    {
        if ($minPa === null) {
            return true;
        }
        if (! array_key_exists('pa', $slugRow)) {
            return true;
        }
        $raw = trim((string) ($slugRow['pa'] ?? ''));
        if (PlayerSheetPlaceholder::isEmptyDisplay($raw)) {
            return false;
        }
        $t = str_replace([',', '%', ' '], '', $raw);
        if ($t === '' || ! is_numeric($t)) {
            return false;
        }

        return (float) $t >= $minPa;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function csvNumericCellMeetsMinimum(?float $min, array $row, int $colIdx): bool
    {
        if ($min === null) {
            return true;
        }
        $raw = trim((string) ($row[$colIdx] ?? ''));
        if (PlayerSheetPlaceholder::isEmptyDisplay($raw)) {
            return false;
        }
        $t = str_replace([',', '%', ' '], '', $raw);
        if ($t === '' || ! is_numeric($t)) {
            return false;
        }

        return (float) $t >= $min;
    }

    /**
     * @param  array<string, string>  $slugRow
     */
    private function slugRowMeetsHeatNumericSlugMinimum(?float $min, array $slugRow, string $slug): bool
    {
        if ($min === null) {
            return true;
        }
        if (! array_key_exists($slug, $slugRow)) {
            return false;
        }
        $raw = trim((string) ($slugRow[$slug] ?? ''));
        if (PlayerSheetPlaceholder::isEmptyDisplay($raw)) {
            return false;
        }
        $t = str_replace([',', '%', ' '], '', $raw);
        if ($t === '' || ! is_numeric($t)) {
            return false;
        }

        return (float) $t >= $min;
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
                if ($key === '' || ! isset($map[$key])) {
                    continue;
                }
                $idx = $map[$key];
                $header = trim((string) ($headers[$idx] ?? ''));
                if (! $this->slugAliasMatchesHeader($canonical, $header, $key)) {
                    continue;
                }
                $resolvedIdx[$canonical] = $idx;
                $resolvedHeader[$canonical] = $header;

                break;
            }
        }

        return [$resolvedIdx, $resolvedHeader];
    }

    /**
     * Avoid mapping strikeout counts ("K") to k_pct or walks ("BB") to bb_pct when both share slug "k"/"bb" with rate columns ("K%", "BB%").
     */
    private function slugAliasMatchesHeader(string $canonical, string $header, string $mapKey): bool
    {
        $norm = DataSourceCsvHeaders::normalizeForMatch($header);

        if ($canonical === 'k_pct' && $mapKey === 'k') {
            return str_contains($norm, '%')
                || str_contains($norm, 'pct')
                || str_contains($norm, 'percent');
        }

        if ($canonical === 'bb_pct' && $mapKey === 'bb') {
            return str_contains($norm, '%')
                || str_contains($norm, 'pct')
                || str_contains($norm, 'percent');
        }

        if ($canonical === 'bats' && $mapKey === 'b') {
            return $norm === 'b';
        }

        if ($canonical === 'throws' && $mapKey === 't') {
            return $norm === 't';
        }

        return true;
    }

    /**
     * @param  array<string, string>  $row  Raw slug row from CSV
     * @return array<string, string>
     */
    private function formatCircuitPgRowForDisplay(array $row): array
    {
        $out = $row;
        foreach (['ops', 'avg', 'obp', 'slg', 'iso'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = HsRangerTraitsDisplay::formatThreeDecimals($out[$k]);
            }
        }
        foreach (['bb_pct', 'k_pct'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = HsRangerTraitsDisplay::formatPercentRateForDisplay($out[$k]);
            }
        }

        return $out;
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
            $yearVal = PlayerSheetPlaceholder::CELL;
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
            return PlayerSheetPlaceholder::CELL;
        }
        $t = trim((string) $row[$idx]);

        return $t !== '' ? $t : PlayerSheetPlaceholder::CELL;
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

    /**
     * Prefer the combined / season row for the latest year when the sheet mixes overall and pitch-type rows.
     *
     * @param  list<list<string|null>>  $sorted  Player rows, newest year first
     */
    private function rowForAggregateStats(array $sorted, ?int $pitchCol, ?int $yearCol): ?array
    {
        if ($sorted === []) {
            return null;
        }
        if ($pitchCol === null) {
            return $sorted[0];
        }

        $topYear = null;
        if ($yearCol !== null) {
            $topYear = (int) preg_replace('/\D/', '', (string) ($sorted[0][$yearCol] ?? ''));
        }

        foreach ($sorted as $r) {
            if ($yearCol !== null) {
                $y = (int) preg_replace('/\D/', '', (string) ($r[$yearCol] ?? ''));
                if ($y !== $topYear) {
                    continue;
                }
            }
            if ($this->isOverallPitchRow($pitchCol, $r)) {
                return $r;
            }
        }

        foreach ($sorted as $r) {
            if ($yearCol !== null) {
                $y = (int) preg_replace('/\D/', '', (string) ($r[$yearCol] ?? ''));
                if ($y !== $topYear) {
                    continue;
                }
            }

            return $r;
        }

        return $sorted[0];
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isOverallPitchRow(int $pitchCol, array $row): bool
    {
        $v = strtoupper(trim((string) ($row[$pitchCol] ?? '')));

        return $v === '' || $this->isAggregatePitchLabelToken($v);
    }

    private function isAggregatePitchLabelToken(string $upper): bool
    {
        static $tokens = [
            'ALL', 'TOTAL', 'OVERALL', 'COMBINED', 'SEASON', 'TTL', 'SUM', 'AGG', 'GENERAL',
            'YEAR', 'FULL', 'TOT',
        ];

        return in_array($upper, $tokens, true);
    }

    /**
     * Maps a pitch / type column to FB, BB, or OS for the Adjustability grid.
     *
     * @param  list<string|null>  $row
     */
    private function pitchBucket(?int $pitchCol, array $row): ?string
    {
        if ($pitchCol === null) {
            return null;
        }
        $raw = strtoupper(trim((string) ($row[$pitchCol] ?? '')));
        if ($raw === '' || $this->isAggregatePitchLabelToken($raw)) {
            return null;
        }

        if (in_array($raw, ['FB', 'FA', 'FT', 'SI', 'FF', '4S', '2S', 'TS'], true)) {
            return 'FB';
        }

        $fastballPhrases = ['FASTBALL', 'FOUR-SEAM', 'FOUR SEAM', '4-SEAM', '4 SEAM', 'SINKER', 'TWO-SEAM', 'TWO SEAM', '2-SEAM', '2 SEAM'];
        foreach ($fastballPhrases as $p) {
            if ($raw === $p || str_contains($raw, $p)) {
                return 'FB';
            }
        }

        if (in_array($raw, ['BB', 'BRK', 'CB', 'SL', 'SW', 'ST', 'CT', 'KC', 'EP', 'SV', 'KN'], true)) {
            return 'BB';
        }

        $breakingPhrases = ['BREAK', 'CURVE', 'SLIDE', 'SWEEP'];
        foreach ($breakingPhrases as $p) {
            if (str_contains($raw, $p)) {
                return 'BB';
            }
        }

        if (in_array($raw, ['OS', 'CH', 'CHANGE', 'CHANGEUP', 'SPLIT', 'FS', 'SF', 'FO', 'SC'], true)) {
            return 'OS';
        }

        if (str_contains($raw, 'CHANGE') || str_contains($raw, 'SPLIT') || str_contains($raw, 'OFFSPEED')) {
            return 'OS';
        }

        return null;
    }
}
