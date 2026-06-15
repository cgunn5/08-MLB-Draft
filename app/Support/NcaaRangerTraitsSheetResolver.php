<?php

namespace App\Support;

use App\Models\DataSourceUpload;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class NcaaRangerTraitsSheetResolver
{
    /** Blocks whose heat uses the dataset comp bucket when {@see HsCompHeatScope} is set (same buckets as HS). */
    private const BLOCK_KEYS_NCAA_COMP_SCOPED_HEAT = [
        'ncaa_perf_ncaa',
        'ncaa_approach_ncaa',
        'ncaa_hunt',
        'ncaa_engine_ncaa',
        'ncaa_platoon',
        'ncaa_adjust_pitch',
    ];

    /**
     * @return array{
     *     has_source: bool,
     *     source_name: ?string,
     *     ncaa_perf_ncaa: list<array<string, string>>,
     *     ncaa_approach_ncaa: list<array<string, string>>,
     *     ncaa_hunt: list<array<string, string>>,
     *     ncaa_engine_ncaa: list<array<string, string>>,
     *     ncaa_platoon: list<array<string, string>>,
     *     ncaa_adjust_pitch: list<array{pitch: string, rows: list<array<string, string>>, heat: list<array<string, string>>}>,
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
            return $this->finalizePayload($empty, $player, $user);
        }

        /** @var list<DataSourceUpload> $assigned */
        $assigned = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('dataset_portal', DataSourceUpload::PORTAL_NCAA)
            ->orderBy('id')
            ->get()
            ->filter(static function (DataSourceUpload $upload): bool {
                $slots = $upload->ncaa_profile_feed_slots;
                if (is_array($slots) && $slots !== []) {
                    return true;
                }

                return DataSourcePitchTypeFeed::fromUpload($upload) !== null;
            })
            ->values()
            ->all();

        if ($assigned === []) {
            return $this->finalizePayload($empty, $player, $user);
        }

        /** Prefer uploads named like "Pitch Types" last so adjustability pitch tables merge from that dataset. */
        usort($assigned, static function (DataSourceUpload $a, DataSourceUpload $b): int {
            $rank = static function (DataSourceUpload $u): int {
                $n = strtolower((string) ($u->name ?? ''));

                return (str_contains($n, 'pitch type') || str_contains($n, 'pitch types')) ? 1 : 0;
            };
            $ra = $rank($a);
            $rb = $rank($b);
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }

            return $a->id <=> $b->id;
        });

        $sourceNames = [];
        $anyHadRows = false;
        $out = $this->emptyPayload();

        foreach ($assigned as $upload) {
            $slots = $upload->ncaa_profile_feed_slots;
            if (! is_array($slots)) {
                $slots = [];
            }
            $blockSet = [];
            foreach ($slots as $slot) {
                if (! is_string($slot)) {
                    continue;
                }
                foreach (NcaaRangerTraitsSheetLayout::blockKeysForProfileSlot($slot) as $bk) {
                    $blockSet[$bk] = true;
                }
            }
            if (DataSourcePitchTypeFeed::fromUpload($upload) !== null) {
                foreach (NcaaRangerTraitsSheetLayout::blockKeysForProfileSlot(DataSourcePitchTypeFeed::PROFILE_SLOT) as $bk) {
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
            return $this->finalizePayload(array_merge($empty, [
                'has_source' => true,
                'source_name' => $label,
            ]), $player, $user);
        }

        $out['has_source'] = true;
        $out['source_name'] = $label;
        $out['radar'] = $this->buildHsOverallRadarPayload($assigned, $player, $compHeatScope);

        return $this->finalizePayload($out, $player, $user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function finalizePayload(array $payload, Player $player, User $user): array
    {
        $payload['hard_contact'] = NcaaHardContactVisualResolver::forPlayer($player, $user);

        return $payload;
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

        $absolutePath = DataSourceUploadStorage::localPath($upload->disk, $upload->path);
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
                if (HsCompHeatScope::cellMatchesBucket($cell, $compHeatScope)) {
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

        foreach (NcaaRangerTraitsSheetLayout::blocks() as $blockKey => $def) {
            if (! isset($allowed[$blockKey])) {
                continue;
            }
            $type = $def['type'];
            if ($type === 'single_year') {
                $sourceRow = $latestAggregate ?? $latest;
                if ($sourceRow === null) {
                    $partial[$blockKey] = null;
                    $partialHeat[$blockKey] = [];
                } else {
                    $rowRaw = $this->extractSlugRow($sourceRow, $headers, $def['slugs'], $slugToIdx, $yearCol, true);
                    $partialHeat[$blockKey] = $this->heatForSlugs(
                        $rowRaw,
                        $def['slugs'],
                        $slugToHeader,
                        $heatRules,
                        $this->heatStatsForBlock($blockKey, $compHeatScope, $compCol, $scopedHeatStats, $heatStats),
                    );
                    $partial[$blockKey] = $rowRaw;
                }

                continue;
            }

            if ($type === 'multi_year') {
                if ($blockKey === 'ncaa_perf_ncaa' && $demographics === null) {
                    $demSource = $latestAggregate ?? $latest;
                    if ($demSource !== null) {
                        $demCells = $this->extractSlugRow($demSource, $headers, ['bats', 'throws', 'demo_age'], $slugToIdx, $yearCol, false);
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
                if (NcaaDraftYearWidePerf::usesWideLayout($headers)
                    && ($blockKey === 'ncaa_perf_ncaa' || $blockKey === 'ncaa_approach_ncaa' || $blockKey === 'ncaa_engine_ncaa' || $blockKey === 'ncaa_platoon')) {
                    $draftCol = DataSourceCsvHeaders::draftYearColumnIndex($headers);
                    $wideSource = $latestAggregate ?? $latest;
                    if ($wideSource !== null && $draftCol !== null) {
                        $draftN = NcaaDraftYearWidePerf::parseDraftYearN($this->cellAt($wideSource, $draftCol));
                        if ($draftN !== null) {
                            $formatter = match ($blockKey) {
                                'ncaa_perf_ncaa' => fn (array $rowRaw): array => $this->formatNcaaPerfNcaaRowForDisplay($rowRaw),
                                'ncaa_approach_ncaa' => fn (array $rowRaw): array => $this->formatNcaaApproachNcaaRowForDisplay($rowRaw),
                                'ncaa_platoon' => fn (array $rowRaw): array => $this->formatNcaaPlatoonRowForDisplay($rowRaw),
                                default => fn (array $rowRaw): array => $this->formatNcaaEngineNcaaRowForDisplay($rowRaw),
                            };
                            [$list, $heatList] = $this->buildNcaaDraftYearWideTierRows(
                                $headers,
                                $wideSource,
                                $draftN,
                                $def['slugs'],
                                $heatMinPaYearly,
                                $heatRules,
                                $heatStats,
                                $blockKey,
                                $compHeatScope,
                                $compCol,
                                $scopedHeatStats,
                                $formatter,
                            );
                            $partial[$blockKey] = $list;
                            $partialHeat[$blockKey] = $heatList;

                            continue;
                        }
                    }
                }

                $list = [];
                $heatList = [];
                $rowsToUse = ($blockKey === 'ncaa_approach_ncaa' || $blockKey === 'ncaa_engine_ncaa' || $blockKey === 'ncaa_platoon' || $blockKey === 'ncaa_hunt')
                    ? array_slice($sorted, 0, 3)
                    : $sorted;
                foreach ($rowsToUse as $r) {
                    $rowRaw = $this->extractSlugRow($r, $headers, $def['slugs'], $slugToIdx, $yearCol, true);
                    $qualHeat = $blockKey === 'ncaa_perf_ncaa'
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
                    if ($blockKey === 'ncaa_perf_ncaa') {
                        $list[] = $this->formatNcaaPerfNcaaRowForDisplay($rowRaw);
                    } elseif ($blockKey === 'ncaa_approach_ncaa') {
                        $list[] = $this->formatNcaaApproachNcaaRowForDisplay($rowRaw);
                    } elseif ($blockKey === 'ncaa_engine_ncaa') {
                        $list[] = $this->formatNcaaEngineNcaaRowForDisplay($rowRaw);
                    } elseif ($blockKey === 'ncaa_platoon') {
                        $list[] = $this->formatNcaaPlatoonRowForDisplay($rowRaw);
                    } elseif ($blockKey === 'ncaa_hunt') {
                        $list[] = $this->formatNcaaHuntRowForDisplay($rowRaw);
                    } else {
                        $list[] = $rowRaw;
                    }
                }
                $partial[$blockKey] = $list;
                $partialHeat[$blockKey] = $heatList;

                continue;
            }

            if ($type === 'pitch_rows') {
                $defSlugs = $def['slugs'];
                $uploadPitchFeed = DataSourcePitchTypeFeed::fromUpload($upload);
                $wanted = array_map('strtoupper', $def['pitches'] ?? []);
                $rulesForHeat = is_array($heatRules) && $heatRules !== [] ? $heatRules : null;
                $heatBrowse = is_array($upload->dataset_browse_settings) ? $upload->dataset_browse_settings : null;
                $volumeIdxPitch = DataSourceCsvHeaders::heatVolumeColumnIndex($headers, $heatBrowse);
                $volumeMinPitch = $heatMinPaYearly;
                if ($volumeMinPitch !== null && $volumeIdxPitch === null) {
                    $volumeMinPitch = null;
                }

                $pitchBlocks = [];
                foreach ($wanted as $pitchLabel) {
                    if ($pitchCol === null && $uploadPitchFeed === null) {
                        $pitchBlocks[] = [
                            'pitch' => (string) $pitchLabel,
                            'rows' => [],
                            'heat' => [],
                        ];

                        continue;
                    }

                    $groupRows = [];
                    foreach ($allDataRows as $r) {
                        if ($compHeatScope !== null && $compCol !== null) {
                            $cbc = trim((string) ($r[$compCol] ?? ''));
                            if (! HsCompHeatScope::cellMatchesBucket($cbc, $compHeatScope)) {
                                continue;
                            }
                        }
                        if ($this->rowMatchesPitchLabel($pitchCol, $r, $pitchLabel, $uploadPitchFeed)) {
                            $groupRows[] = $r;
                        }
                    }
                    $groupHeatStats = ($rulesForHeat !== null)
                        ? DataSourceHeatColumnStats::compute($headers, $groupRows, $rulesForHeat, $volumeIdxPitch, $volumeMinPitch)
                        : null;
                    $heatStatsForPitch = $groupHeatStats ?? $this->heatStatsForBlock('ncaa_adjust_pitch', $compHeatScope, $compCol, $scopedHeatStats, $heatStats);

                    $selectedCsvRows = [];
                    $seenYearKeys = [];
                    foreach ($sorted as $r) {
                        if (! $this->rowMatchesPitchLabel($pitchCol, $r, $pitchLabel, $uploadPitchFeed)) {
                            continue;
                        }
                        if ($yearCol !== null) {
                            $yKey = $this->yearDedupKeyFromRow($yearCol, $r);
                            if ($yKey !== '') {
                                if (isset($seenYearKeys[$yKey])) {
                                    continue;
                                }
                                $seenYearKeys[$yKey] = true;
                            }
                        }
                        $selectedCsvRows[] = $r;
                        if (count($selectedCsvRows) >= 3) {
                            break;
                        }
                    }

                    $rows = [];
                    $heatRows = [];
                    if ($selectedCsvRows === []) {
                        $rows[] = array_merge(
                            ['year' => PlayerSheetPlaceholder::CELL],
                            $this->emptySlugCells($defSlugs),
                        );
                        $heatRows[] = [];
                    } else {
                        foreach ($selectedCsvRows as $found) {
                            $one = $this->extractSlugRow($found, $headers, $defSlugs, $slugToIdx, $yearCol, true);
                            $pitchQualHeat = true;
                            if ($volumeMinPitch !== null && $volumeIdxPitch !== null) {
                                $pitchQualHeat = $this->csvNumericCellMeetsMinimum($volumeMinPitch, $found, $volumeIdxPitch);
                            }
                            $heatRows[] = $this->heatForSlugs(
                                $one,
                                $defSlugs,
                                $slugToHeader,
                                $heatRules,
                                $heatStatsForPitch,
                                null,
                                $pitchQualHeat,
                            );
                            $rows[] = $one;
                        }
                    }

                    $pitchBlocks[] = [
                        'pitch' => $pitchLabel,
                        'rows' => $rows,
                        'heat' => $heatRows,
                    ];
                }

                $partial[$blockKey] = $pitchBlocks;
                $partialHeat[$blockKey] = [];
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
            if ($key === 'ncaa_adjust_pitch' && is_array($value)) {
                $existing = is_array($out[$key] ?? null) ? $out[$key] : [];
                $out[$key] = $this->mergeNcaaAdjustPitchBlocks($existing, $value);
            } else {
                $out[$key] = $value;
            }
        }
        foreach ($material['partial_heat'] as $key => $value) {
            $out['cell_heat'][$key] = $value;
        }
        if ($material['demographics'] !== null) {
            $out['overall_demographics'] = $material['demographics'];
        }
    }

    /**
     * @param  list<array{pitch: string, rows: list<array<string, string>>, heat: list<array<string, string>>}>  $existing
     * @param  list<array{pitch: string, rows: list<array<string, string>>, heat: list<array<string, string>>}>  $incoming
     * @return list<array{pitch: string, rows: list<array<string, string>>, heat: list<array<string, string>>}>
     */
    private function mergeNcaaAdjustPitchBlocks(array $existing, array $incoming): array
    {
        /** @var array<string, array{pitch: string, rows: list<array<string, string>>, heat: list<array<string, string>>}> $byPitch */
        $byPitch = [];
        foreach ($existing as $block) {
            if (isset($block['pitch'])) {
                $byPitch[(string) $block['pitch']] = $block;
            }
        }
        foreach ($incoming as $block) {
            if (! isset($block['pitch'])) {
                continue;
            }
            $pitch = (string) $block['pitch'];
            $incomingRows = is_array($block['rows'] ?? null) ? $block['rows'] : [];
            $existingBlock = $byPitch[$pitch] ?? null;
            $existingRows = is_array($existingBlock['rows'] ?? null) ? $existingBlock['rows'] : [];

            if ($this->ncaaAdjustPitchBlockHasStats($incomingRows)) {
                $byPitch[$pitch] = $block;
            } elseif ($incomingRows !== [] && ($existingBlock === null || $existingRows === [] || ! $this->ncaaAdjustPitchBlockHasStats($existingRows))) {
                $byPitch[$pitch] = $block;
            } elseif ($existingBlock === null) {
                $byPitch[$pitch] = $block;
            }
        }

        $out = [];
        foreach (DataSourcePitchTypeFeed::allowed() as $pitch) {
            if (isset($byPitch[$pitch])) {
                $out[] = $byPitch[$pitch];
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function ncaaAdjustPitchBlockHasStats(array $rows): bool
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($row as $key => $value) {
                if ($key === 'year') {
                    continue;
                }
                if (! PlayerSheetPlaceholder::isEmptyDisplay((string) $value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function rowMatchesPitchLabel(?int $pitchCol, array $row, string $pitchLabel, ?string $uploadPitchFeed): bool
    {
        if ($pitchCol !== null) {
            return $this->pitchBucket($pitchCol, $row) === $pitchLabel;
        }

        return $uploadPitchFeed !== null && $uploadPitchFeed === $pitchLabel;
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
     *     ncaa_perf_ncaa: list<array<string, string>>,
     *     ncaa_approach_ncaa: list<array<string, string>>,
     *     ncaa_hunt: list<array<string, string>>,
     *     ncaa_engine_ncaa: list<array<string, string>>,
     *     ncaa_platoon: list<array<string, string>>,
     *     ncaa_adjust_pitch: list<array{pitch: string, rows: list<array<string, string>>, heat: list<array<string, string>>}>,
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
            'ncaa_perf_ncaa' => [],
            'ncaa_approach_ncaa' => [],
            'ncaa_hunt' => [],
            'ncaa_engine_ncaa' => [],
            'ncaa_platoon' => [],
            'ncaa_adjust_pitch' => [
                ['pitch' => 'FB', 'rows' => [], 'heat' => []],
                ['pitch' => 'BB', 'rows' => [], 'heat' => []],
                ['pitch' => 'OS', 'rows' => [], 'heat' => []],
            ],
            'cell_heat' => $this->emptyCellHeatSkeleton(),
            'overall_demographics' => null,
            'radar' => null,
            'hard_contact' => null,
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
        $slots = $upload->ncaa_profile_feed_slots;
        if (! is_array($slots) || $slots === []) {
            return null;
        }

        $absolutePath = DataSourceUploadStorage::localPath($upload->disk, $upload->path);
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
                    return HsCompHeatScope::cellMatchesBucket(trim((string) ($r[$compCol] ?? '')), $compHeatScope);
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
                if (HsCompHeatScope::cellMatchesBucket($cell, $compHeatScope)) {
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
        foreach (array_keys(NcaaRangerTraitsSheetLayout::blocks()) as $bk) {
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
            && in_array($blockKey, self::BLOCK_KEYS_NCAA_COMP_SCOPED_HEAT, true)
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

        $aliases = NcaaRangerTraitsSheetLayout::slugAliases();
        $resolvedIdx = [];
        $resolvedHeader = [];
        foreach ($aliases as $canonical => $aliasList) {
            foreach ($aliasList as $alias) {
                $key = DataSourceCsvHeaders::aliasSlug($alias);
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

        if ($canonical === 'ak_pct' && $mapKey === 'ak') {
            return str_contains($norm, '%')
                || str_contains($norm, 'pct')
                || str_contains($norm, 'percent');
        }

        if ($canonical === 'abb_pct' && $mapKey === 'abb') {
            return str_contains($norm, '%')
                || str_contains($norm, 'pct')
                || str_contains($norm, 'percent');
        }

        if ($canonical === 'barrel_pct' && $mapKey === 'brl') {
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
     * @param  array<string, string>  $row  Raw slug row from CSV
     * @return array<string, string>
     */
    private function formatNcaaPerfNcaaRowForDisplay(array $row): array
    {
        $out = $row;
        foreach (['ops', 'avg', 'slg', 'iso', 'xwoba', 'woba', 'awoba'] as $k) {
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
     * @param  array<string, string>  $row  Raw slug row from CSV
     * @return array<string, string>
     */
    private function formatNcaaApproachNcaaRowForDisplay(array $row): array
    {
        $out = $row;
        foreach (['k_pct', 'ak_pct', 'bb_pct', 'abb_pct', 'sw_pct', 'ch_pct', 'swm_pct', 'iz_swm_pct'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = HsRangerTraitsDisplay::formatPercentRateForDisplay($out[$k]);
            }
        }
        foreach (['k_bb', 'ak_bb'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = HsRangerTraitsDisplay::formatTwoDecimalRatio($out[$k]);
            }
        }
        if (array_key_exists('swdec', $out)) {
            $out['swdec'] = HsRangerTraitsDisplay::formatIntegerForDisplay($out['swdec']);
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $row  Raw slug row from CSV
     * @return array<string, string>
     */
    private function formatNcaaEngineNcaaRowForDisplay(array $row): array
    {
        $out = $row;
        foreach (['ev70', 'ev95', 'max_ev'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = HsRangerTraitsDisplay::formatOneDecimalDisplay($out[$k]);
            }
        }
        foreach (['barrel_pct', 'gb_pct', 'ld_pct'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = HsRangerTraitsDisplay::formatPercentRateForDisplay($out[$k]);
            }
        }
        if (array_key_exists('bip110', $out)) {
            $out['bip110'] = HsRangerTraitsDisplay::formatIntegerForDisplay($out['bip110']);
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $row  Raw slug row from CSV (includes year)
     * @return array<string, string>
     */
    private function formatNcaaPlatoonRowForDisplay(array $row): array
    {
        $out = $row;
        foreach (['ops_vs_r', 'ops_vs_l', 'iso_vs_r', 'iso_vs_l'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = HsRangerTraitsDisplay::formatThreeDecimals($out[$k]);
            }
        }
        foreach (['k_bb_vs_r', 'k_bb_vs_l'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = HsRangerTraitsDisplay::formatTwoDecimalRatio($out[$k]);
            }
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $row  Raw slug row from CSV
     * @return array<string, string>
     */
    private function formatNcaaHuntRowForDisplay(array $row): array
    {
        $out = $row;
        foreach (['cov_pct', 'hunt_pct', 'lt2k_hunt_pct'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = HsRangerTraitsDisplay::formatPercentRateForDisplay($out[$k]);
            }
        }
        foreach (['nz_xops', 'onz_xops'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = HsRangerTraitsDisplay::formatThreeDecimals($out[$k]);
            }
        }
        if (array_key_exists('delta', $out)) {
            $out['delta'] = HsRangerTraitsDisplay::formatThreeDecimals($out['delta']);
        }

        return $out;
    }

    /**
     * One CSV row per player: stats for draft year N, N-1, N-2 in separate columns (plain vs "(N-1)" / "(N-2)" headers).
     *
     * @param  list<string>  $headers
     * @param  list<string|null>  $sourceRow
     * @param  list<string>  $slugs
     * @param  callable(array<string, string>): array<string, string>  $formatDisplayRow
     * @return array{0: list<array<string, string>>, 1: list<array<string, string>>}
     */
    private function buildNcaaDraftYearWideTierRows(
        array $headers,
        array $sourceRow,
        int $draftYearN,
        array $slugs,
        ?float $heatMinPaYearly,
        ?array $heatRules,
        ?array $heatStats,
        string $blockKey,
        ?string $compHeatScope,
        ?int $compCol,
        ?array $scopedHeatStats,
        callable $formatDisplayRow,
    ): array {
        $map = NcaaDraftYearWidePerf::tierSlugColumnMap($headers, $slugs);
        $list = [];
        $heatList = [];
        for ($tier = 0; $tier <= 2; $tier++) {
            $year = (string) ($draftYearN - $tier);
            $rowRaw = ['year' => $year];
            /** @var array<string, string> $slugToHeaderThis */
            $slugToHeaderThis = [];
            foreach ($slugs as $slug) {
                $idx = $map[$tier][$slug] ?? null;
                $rowRaw[$slug] = $this->cellAt($sourceRow, $idx);
                if ($idx !== null) {
                    $slugToHeaderThis[$slug] = trim((string) ($headers[$idx] ?? ''));
                }
            }
            $qualHeat = $this->slugRowMeetsHeatPaMinimum($heatMinPaYearly, $rowRaw);
            $heatList[] = $this->heatForSlugs(
                $rowRaw,
                $slugs,
                $slugToHeaderThis,
                $heatRules,
                $this->heatStatsForBlock($blockKey, $compHeatScope, $compCol, $scopedHeatStats, $heatStats),
                null,
                $qualHeat,
            );
            $list[] = $formatDisplayRow($rowRaw);
        }

        return [$list, $heatList];
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
            $ya = $this->calendarYearSortKeyFromCell((string) ($a[$yearCol] ?? ''));
            $yb = $this->calendarYearSortKeyFromCell((string) ($b[$yearCol] ?? ''));

            return $yb <=> $ya;
        });

        return $rows;
    }

    /**
     * Prefer a 4-digit calendar year (19xx/20xx) inside the cell so values like "2024-25" sort as 2024, not 202425.
     */
    private function calendarYearSortKeyFromCell(string $raw): int
    {
        $t = trim($raw);
        if ($t === '') {
            return 0;
        }
        if (preg_match('/\b(19|20)\d{2}\b/', $t, $m)) {
            return (int) $m[0];
        }
        $digits = preg_replace('/\D/', '', $t) ?? '';

        return $digits !== '' ? (int) $digits : 0;
    }

    /**
     * Distinct-year selection for pitch-type rows (matches {@see calendarYearSortKeyFromCell} when possible).
     */
    private function yearDedupKeyFromRow(?int $yearCol, array $row): string
    {
        if ($yearCol === null) {
            return '';
        }
        $raw = trim((string) ($row[$yearCol] ?? ''));
        if ($raw === '') {
            return '';
        }
        if (preg_match('/\b(19|20)\d{2}\b/', $raw, $m)) {
            return $m[0];
        }

        return strtolower($raw);
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

        $fastballPhrases = [
            'FASTBALL', 'FOUR-SEAM', 'FOUR SEAM', 'FOURSEAM', '4-SEAM', '4 SEAM', 'SINKER',
            'TWO-SEAM', 'TWO SEAM', '2-SEAM', '2 SEAM', 'HARD', 'VELO',
        ];
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

        if (in_array($raw, ['OS', 'CH', 'CHANGE', 'CHANGEUP', 'SPLIT', 'FS', 'SF', 'FO', 'SC', 'SPLT', 'SPL'], true)) {
            return 'OS';
        }

        if (str_contains($raw, 'CHANGE') || str_contains($raw, 'SPLIT') || str_contains($raw, 'OFFSPEED')) {
            return 'OS';
        }

        return null;
    }
}
