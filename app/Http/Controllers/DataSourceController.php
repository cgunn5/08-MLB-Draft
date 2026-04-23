<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppendDataSourceRowRequest;
use App\Http\Requests\DataSourceRowUpdateRequest;
use App\Http\Requests\StoreDataSourceUploadRequest;
use App\Http\Requests\UpdateDataSourceUploadSettingsRequest;
use App\Models\DataSourceUpload;
use App\Support\CareerPgMasterUploadService;
use App\Support\CareerPgStatsAggregator;
use App\Support\DataSourceCsvHeaders;
use App\Support\DataSourceHeatColumnStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DataSourceController extends Controller
{
    private const GROUP_VALUE_EMPTY_SENTINEL = '__EMPTY__';

    public function index(): View
    {
        $user = auth()->user();
        if ($user !== null) {
            CareerPgMasterUploadService::syncForUser($user);
        }

        $uploads = DataSourceUpload::query()
            ->where('user_id', auth()->id())
            ->orderByRaw('CASE WHEN upload_kind = ? THEN 0 ELSE 1 END', [DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER])
            ->orderByDesc('id')
            ->get();

        $initialActiveId = null;
        if ($uploads->isNotEmpty()) {
            $queryDataset = request()->query('dataset');
            $preferred = is_numeric($queryDataset) ? (int) $queryDataset : null;
            $initialActiveId = $preferred !== null && $uploads->contains(fn (DataSourceUpload $u): bool => $u->id === $preferred)
                ? $preferred
                : $uploads->first()->id;
        }

        return view('data-sources.index', [
            'uploads' => $uploads,
            'initialActiveId' => $initialActiveId,
        ]);
    }

    public function store(StoreDataSourceUploadRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $storedName = Str::uuid()->toString().'.csv';
        $path = $file->storeAs('data-source-uploads', $storedName, 'local');

        if ($path === false) {
            return back()
                ->withInput($request->except('file'))
                ->withErrors(['file' => __('Could not store the file.')]);
        }

        $absolutePath = Storage::disk('local')->path($path);

        try {
            $stats = $this->csvUploadStats($absolutePath);
        } catch (\Throwable) {
            Storage::disk('local')->delete($path);

            return back()
                ->withInput($request->except('file'))
                ->withErrors(['file' => __('The file could not be read as a CSV.')]);
        }

        DataSourceUpload::query()->create([
            'user_id' => $request->user()->id,
            'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
            'name' => $request->validated('name'),
            'original_filename' => $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'header_row' => $stats['header_row'],
            'row_count' => $stats['row_count'],
        ]);

        return redirect()
            ->route('data-sources.index')
            ->with('status', __('CSV uploaded.'));
    }

    public function show(Request $request, DataSourceUpload $dataSourceUpload): RedirectResponse
    {
        abort_unless($dataSourceUpload->user_id === $request->user()->id, 404);

        return redirect()->route('data-sources.index', [
            'dataset' => $dataSourceUpload->id,
        ]);
    }

    public function destroyUpload(Request $request, DataSourceUpload $dataSourceUpload): JsonResponse|RedirectResponse
    {
        abort_unless($dataSourceUpload->user_id === $request->user()->id, 404);

        if (! $dataSourceUpload->isCareerPgMaster()) {
            $disk = Storage::disk($dataSourceUpload->disk);
            if ($dataSourceUpload->path !== '' && $disk->exists($dataSourceUpload->path)) {
                $disk->delete($dataSourceUpload->path);
            }
        }

        $dataSourceUpload->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'redirect' => route('data-sources.index'),
            ]);
        }

        return redirect()
            ->route('data-sources.index')
            ->with('status', __('Dataset deleted.'));
    }

    public function playerNames(Request $request, DataSourceUpload $dataSourceUpload): JsonResponse
    {
        abort_unless($dataSourceUpload->user_id === $request->user()->id, 404);

        if ($dataSourceUpload->isCareerPgMaster()) {
            $mat = $this->materializeCareerPgData($dataSourceUpload);
            if ($mat === null) {
                return response()->json(['names' => []]);
            }
            $headers = $mat['headers'];
            $playerIdx = DataSourceCsvHeaders::playerColumnIndex($headers);
            /** @var array<string, string> $byLower */
            $byLower = [];
            foreach ($mat['rows'] as $row) {
                $raw = isset($row[$playerIdx]) ? trim((string) $row[$playerIdx]) : '';
                if ($raw === '') {
                    continue;
                }
                $key = strtolower($raw);
                if (! isset($byLower[$key])) {
                    $byLower[$key] = $raw;
                }
            }
            $names = array_values($byLower);
            natcasesort($names);

            return response()->json(['names' => array_values($names)]);
        }

        $headers = $dataSourceUpload->header_row;
        $playerIdx = DataSourceCsvHeaders::playerColumnIndex($headers);
        $absolutePath = Storage::disk($dataSourceUpload->disk)->path($dataSourceUpload->path);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            abort(404);
        }

        try {
            fgetcsv($handle);
            /** @var array<string, string> $byLower */
            $byLower = [];
            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isBlankCsvRow($row)) {
                    continue;
                }
                $raw = isset($row[$playerIdx]) ? trim((string) $row[$playerIdx]) : '';
                if ($raw === '') {
                    continue;
                }
                $key = strtolower($raw);
                if (! isset($byLower[$key])) {
                    $byLower[$key] = $raw;
                }
            }
        } finally {
            fclose($handle);
        }

        $names = array_values($byLower);
        natcasesort($names);

        return response()->json(['names' => array_values($names)]);
    }

    public function tableData(Request $request, DataSourceUpload $dataSourceUpload): JsonResponse
    {
        abort_unless($dataSourceUpload->user_id === $request->user()->id, 404);

        if ($dataSourceUpload->isCareerPgMaster()) {
            return $this->careerPgMasterTableData($request, $dataSourceUpload);
        }

        $perPage = 50;
        $headers = $dataSourceUpload->header_row;
        $playerIdx = DataSourceCsvHeaders::playerColumnIndex($headers);
        $legacyFilter = trim((string) $request->query('filter', ''));
        $playersQuery = $request->query('players');
        /** @var list<string> $playerTerms */
        $playerTerms = [];
        if (is_array($playersQuery)) {
            foreach ($playersQuery as $p) {
                $s = trim((string) $p);
                if ($s !== '') {
                    $playerTerms[] = $s;
                }
            }
            $playerTerms = array_values(array_unique($playerTerms));
        } elseif ($playersQuery !== null && (string) $playersQuery !== '') {
            $playerTerms = [trim((string) $playersQuery)];
        }

        $absolutePath = Storage::disk($dataSourceUpload->disk)->path($dataSourceUpload->path);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        $useExactPlayerMatch = $playerTerms !== [];
        $filterTerms = $useExactPlayerMatch ? $playerTerms : ($legacyFilter !== '' ? [$legacyFilter] : []);
        $playerFilterActive = $filterTerms !== [];

        $order = $this->normalizeColumnOrder($dataSourceUpload->column_order, count($headers));

        $thresholdRules = $this->parseColumnThresholds($request, count($headers));
        $thresholdActive = $thresholdRules !== [];

        $sortColRaw = $request->query('sort_column');
        $sortDirRaw = strtolower((string) $request->query('sort_direction', 'asc'));
        $sortAscending = $sortDirRaw !== 'desc';
        $sortColumnIndex = null;
        if ($sortColRaw !== null && $sortColRaw !== '' && is_numeric($sortColRaw)) {
            $sortColumnIndex = (int) $sortColRaw;
        }
        if ($sortColumnIndex !== null && ($sortColumnIndex < 0 || $sortColumnIndex >= count($headers))) {
            $sortColumnIndex = null;
        }

        $sortActive = $sortColumnIndex !== null;

        $groupColRaw = $request->query('group_column');
        $groupColumnIndex = null;
        if ($groupColRaw !== null && $groupColRaw !== '' && is_numeric($groupColRaw)) {
            $gci = (int) $groupColRaw;
            if ($gci >= 0 && $gci < count($headers)) {
                $groupColumnIndex = $gci;
            }
        }
        $groupValueRaw = $request->query('group_value');
        $groupFilterActive = $groupColumnIndex !== null && $groupValueRaw !== null;
        $groupValueMatch = null;
        if ($groupFilterActive) {
            $groupValueMatch = $groupValueRaw === self::GROUP_VALUE_EMPTY_SENTINEL ? '' : (string) $groupValueRaw;
        }

        $needsFullMaterialize = $sortActive || $thresholdActive || $groupFilterActive;

        $tableFilterActive = $playerFilterActive || $thresholdActive || $groupFilterActive;

        $heatRulesForResponse = $dataSourceUpload->heat_rules;
        if (! is_array($heatRulesForResponse)) {
            $heatRulesForResponse = [];
        }

        $browse = is_array($dataSourceUpload->dataset_browse_settings) ? $dataSourceUpload->dataset_browse_settings : null;
        $effectiveHeatMinPa = $this->effectiveHeatMinPaForTable($request, $browse);
        $liveHeatMinQuery = $this->heatMinPaQueryOrNull($request);
        $heatMinForSubset = $groupFilterActive ? $effectiveHeatMinPa : null;

        if (! $playerFilterActive && ! $needsFullMaterialize) {
            $totalRows = $dataSourceUpload->row_count;
            $lastPage = max(1, (int) ceil(max(1, $totalRows) / $perPage));
            $page = min(max(1, (int) $request->query('page', 1)), $lastPage);

            [$rows, $ordinals] = $this->readCsvDataPage(
                $absolutePath,
                $headers,
                $page,
                $perPage
            );
            [$headers, $rows] = $this->reorderPlayerFirst($headers, $rows, $playerIdx);
            [$headers, $rows] = $this->applyColumnOrderPermutation($headers, $rows, $order);
            if ($liveHeatMinQuery !== null && $this->heatRulesAreEnabled($dataSourceUpload)) {
                $computed = $this->computeHeatColumnStatsForUpload($dataSourceUpload, $liveHeatMinQuery);
                $heatColumnStatsOut = $computed === [] ? (object) [] : $computed;
            } else {
                $heatColumnStatsOut = $dataSourceUpload->heat_column_stats ?? (object) [];
            }
        } elseif ($playerFilterActive && ! $needsFullMaterialize) {
            [$totalRows, $rows, $page, $lastPage, $ordinals] = $this->readCsvFilteredPage(
                $absolutePath,
                $headers,
                $perPage,
                (int) $request->query('page', 1),
                $filterTerms,
                $useExactPlayerMatch,
                $playerIdx
            );
            [$headers, $rows] = $this->reorderPlayerFirst($headers, $rows, $playerIdx);
            [$headers, $rows] = $this->applyColumnOrderPermutation($headers, $rows, $order);
            if ($liveHeatMinQuery !== null && $this->heatRulesAreEnabled($dataSourceUpload)) {
                $computed = $this->computeHeatColumnStatsForUpload($dataSourceUpload, $liveHeatMinQuery);
                $heatColumnStatsOut = $computed === [] ? (object) [] : $computed;
            } else {
                $heatColumnStatsOut = $dataSourceUpload->heat_column_stats ?? (object) [];
            }
        } else {
            $rawRowsForGroupScopedHeatStats = null;
            if ($playerFilterActive && $groupFilterActive) {
                [$rawAll, $rawAllOrdinals] = $this->collectCsvRowsWithOrdinals(
                    $absolutePath,
                    count($headers),
                    null,
                    [],
                    false
                );
                $rawRowsForGroupScopedHeatStats = $rawAll;
                $rawRows = [];
                $rawOrdinals = [];
                foreach ($rawAll as $i => $row) {
                    $cell = (string) ($row[$playerIdx] ?? '');
                    if ($this->csvRowMatchesPlayerTerms($cell, $filterTerms, $useExactPlayerMatch)) {
                        $rawRows[] = $row;
                        $rawOrdinals[] = $rawAllOrdinals[$i];
                    }
                }
            } elseif (! $playerFilterActive) {
                [$rawRows, $rawOrdinals] = $this->collectCsvRowsWithOrdinals(
                    $absolutePath,
                    count($headers),
                    null,
                    [],
                    false
                );
            } else {
                [$rawRows, $rawOrdinals] = $this->collectCsvRowsWithOrdinals(
                    $absolutePath,
                    count($headers),
                    $playerIdx,
                    $filterTerms,
                    $useExactPlayerMatch
                );
            }
            $page = (int) $request->query('page', 1);
            [$headers, $rows, $ordinals, $page, $lastPage, $totalRows, $subsetHeatStats] = $this->finalizePagedDisplayRows(
                $headers,
                $rawRows,
                $rawOrdinals,
                $playerIdx,
                $order,
                $thresholdRules,
                $sortColumnIndex,
                $sortAscending,
                $page,
                $perPage,
                $groupFilterActive ? $heatRulesForResponse : null,
                $groupFilterActive ? $groupColumnIndex : null,
                $groupFilterActive ? $groupValueMatch : null,
                $heatMinForSubset,
                $rawRowsForGroupScopedHeatStats
            );
            if ($subsetHeatStats !== null) {
                $heatColumnStatsOut = $subsetHeatStats;
            } elseif ($liveHeatMinQuery !== null && $this->heatRulesAreEnabled($dataSourceUpload)) {
                $computed = $this->computeHeatColumnStatsForUpload($dataSourceUpload, $liveHeatMinQuery);
                $heatColumnStatsOut = $computed === [] ? (object) [] : $computed;
            } else {
                $heatColumnStatsOut = $dataSourceUpload->heat_column_stats ?? (object) [];
            }
        }

        $from = $totalRows === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $to = min($page * $perPage, $totalRows);

        $heatRowPaOk = $this->heatRowPaOkFlags($effectiveHeatMinPa, $headers, $rows);

        return response()->json([
            'headers' => $headers,
            'rows' => $rows,
            'row_ordinals' => $ordinals,
            'page' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'lastPage' => $lastPage,
            'from' => $from,
            'to' => $to,
            'original_filename' => $dataSourceUpload->original_filename,
            'filter_active' => $tableFilterActive,
            'column_order' => $order,
            'sort' => $sortActive ? [
                'column' => $sortColumnIndex,
                'direction' => $sortAscending ? 'asc' : 'desc',
            ] : null,
            'heat_rules' => $dataSourceUpload->heat_rules ?? (object) [],
            'heat_column_stats' => $heatColumnStatsOut ?? (object) [],
            'heat_pa_qualifier' => $this->heatPaQualifierForDisplay($headers, $effectiveHeatMinPa),
            'heat_row_pa_ok' => $heatRowPaOk,
            'group' => $groupColumnIndex !== null ? [
                'column' => $groupColumnIndex,
                'active' => $groupFilterActive,
            ] : null,
        ]);
    }

    public function groupColumnValues(Request $request, DataSourceUpload $dataSourceUpload): JsonResponse
    {
        abort_unless($dataSourceUpload->user_id === $request->user()->id, 404);

        if ($dataSourceUpload->isCareerPgMaster()) {
            return $this->careerPgMasterGroupColumnValues($request, $dataSourceUpload);
        }

        $fileHeaders = $dataSourceUpload->header_row;
        $playerIdx = DataSourceCsvHeaders::playerColumnIndex($fileHeaders);
        $groupColRaw = $request->query('group_column');
        if ($groupColRaw === null || $groupColRaw === '' || ! is_numeric($groupColRaw)) {
            return response()->json(['values' => []]);
        }
        $groupColumnIndex = (int) $groupColRaw;
        if ($groupColumnIndex < 0 || $groupColumnIndex >= count($fileHeaders)) {
            return response()->json(['values' => []]);
        }

        $playersQuery = $request->query('players');
        /** @var list<string> $playerTerms */
        $playerTerms = [];
        if (is_array($playersQuery)) {
            foreach ($playersQuery as $p) {
                $s = trim((string) $p);
                if ($s !== '') {
                    $playerTerms[] = $s;
                }
            }
            $playerTerms = array_values(array_unique($playerTerms));
        } elseif ($playersQuery !== null && (string) $playersQuery !== '') {
            $playerTerms = [trim((string) $playersQuery)];
        }

        $useExactPlayerMatch = $playerTerms !== [];
        $filterTerms = $useExactPlayerMatch ? $playerTerms : [];
        $playerFilterActive = $filterTerms !== [];

        $absolutePath = Storage::disk($dataSourceUpload->disk)->path($dataSourceUpload->path);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        $order = $this->normalizeColumnOrder($dataSourceUpload->column_order, count($fileHeaders));

        if (! $playerFilterActive) {
            [$rawRows, $rawOrdinals] = $this->collectCsvRowsWithOrdinals(
                $absolutePath,
                count($fileHeaders),
                null,
                [],
                false
            );
        } else {
            [$rawRows, $rawOrdinals] = $this->collectCsvRowsWithOrdinals(
                $absolutePath,
                count($fileHeaders),
                $playerIdx,
                $filterTerms,
                $useExactPlayerMatch
            );
        }

        [$dispHeaders, $dispRows] = $this->reorderPlayerFirst($fileHeaders, $rawRows, $playerIdx);
        [$dispHeaders, $dispRows] = $this->applyColumnOrderPermutation($dispHeaders, $dispRows, $order);

        if ($groupColumnIndex < 0 || $groupColumnIndex >= count($dispHeaders)) {
            return response()->json(['values' => []]);
        }

        /** @var array<string, true> $seen */
        $seen = [];
        foreach ($dispRows as $row) {
            $cell = trim((string) ($row[$groupColumnIndex] ?? ''));
            $seen[$cell] = true;
        }
        $values = array_keys($seen);
        usort($values, function (string $a, string $b): int {
            if ($a === '' && $b !== '') {
                return 1;
            }
            if ($a !== '' && $b === '') {
                return -1;
            }

            return strnatcasecmp($a, $b);
        });

        return response()->json(['values' => array_values($values)]);
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string>>, row_count: int, player_column_index: int}|null
     */
    private function materializeCareerPgData(DataSourceUpload $career): ?array
    {
        $source = CareerPgMasterUploadService::resolveSourceForCareerMaster($career);
        if ($source === null) {
            return null;
        }

        return CareerPgStatsAggregator::fromSourceUpload($source);
    }

    private function careerPgMasterTableData(Request $request, DataSourceUpload $dataSourceUpload): JsonResponse
    {
        $perPage = 50;
        $materialized = $this->materializeCareerPgData($dataSourceUpload);

        $legacyFilter = trim((string) $request->query('filter', ''));
        $playersQuery = $request->query('players');
        /** @var list<string> $playerTerms */
        $playerTerms = [];
        if (is_array($playersQuery)) {
            foreach ($playersQuery as $p) {
                $s = trim((string) $p);
                if ($s !== '') {
                    $playerTerms[] = $s;
                }
            }
            $playerTerms = array_values(array_unique($playerTerms));
        } elseif ($playersQuery !== null && (string) $playersQuery !== '') {
            $playerTerms = [trim((string) $playersQuery)];
        }

        if ($materialized === null) {
            /** @var list<string> $headers */
            $headers = array_map(static fn ($h) => is_string($h) ? $h : '', $dataSourceUpload->header_row ?? []);
            $pIdx = DataSourceCsvHeaders::playerColumnIndex($headers);
            $ord = $this->normalizeColumnOrder($dataSourceUpload->column_order, count($headers));
            $dummyRow = [array_fill(0, count($headers), '')];
            [$dispH] = $this->reorderPlayerFirst($headers, $dummyRow, $pIdx);
            [$dispH] = $this->applyColumnOrderPermutation($dispH, $dummyRow, $ord);
            $browse = is_array($dataSourceUpload->dataset_browse_settings) ? $dataSourceUpload->dataset_browse_settings : null;
            $effectiveHeatMinPa = $this->effectiveHeatMinPaForTable($request, $browse);

            return response()->json([
                'headers' => $dispH,
                'rows' => [],
                'row_ordinals' => [],
                'page' => 1,
                'perPage' => $perPage,
                'totalRows' => 0,
                'lastPage' => 1,
                'from' => 0,
                'to' => 0,
                'original_filename' => $dataSourceUpload->original_filename,
                'filter_active' => false,
                'column_order' => $ord,
                'sort' => null,
                'heat_rules' => $dataSourceUpload->heat_rules ?? (object) [],
                'heat_column_stats' => $dataSourceUpload->heat_column_stats ?? (object) [],
                'heat_pa_qualifier' => $this->heatPaQualifierForDisplay($dispH, $effectiveHeatMinPa),
                'heat_row_pa_ok' => null,
                'group' => null,
            ]);
        }

        $fileHeaders = $materialized['headers'];
        $playerIdx = DataSourceCsvHeaders::playerColumnIndex($fileHeaders);
        $useExactPlayerMatch = $playerTerms !== [];
        $filterTerms = $useExactPlayerMatch ? $playerTerms : ($legacyFilter !== '' ? [$legacyFilter] : []);
        $playerFilterActive = $filterTerms !== [];

        $order = $this->normalizeColumnOrder($dataSourceUpload->column_order, count($fileHeaders));

        $thresholdRules = $this->parseColumnThresholds($request, count($fileHeaders));

        $sortColRaw = $request->query('sort_column');
        $sortDirRaw = strtolower((string) $request->query('sort_direction', 'asc'));
        $sortAscending = $sortDirRaw !== 'desc';
        $sortColumnIndex = null;
        if ($sortColRaw !== null && $sortColRaw !== '' && is_numeric($sortColRaw)) {
            $sortColumnIndex = (int) $sortColRaw;
        }
        if ($sortColumnIndex !== null && ($sortColumnIndex < 0 || $sortColumnIndex >= count($fileHeaders))) {
            $sortColumnIndex = null;
        }

        $sortActive = $sortColumnIndex !== null;

        $groupColRaw = $request->query('group_column');
        $groupColumnIndex = null;
        if ($groupColRaw !== null && $groupColRaw !== '' && is_numeric($groupColRaw)) {
            $gci = (int) $groupColRaw;
            if ($gci >= 0 && $gci < count($fileHeaders)) {
                $groupColumnIndex = $gci;
            }
        }
        $groupValueRaw = $request->query('group_value');
        $groupFilterActive = $groupColumnIndex !== null && $groupValueRaw !== null;
        $groupValueMatch = null;
        if ($groupFilterActive) {
            $groupValueMatch = $groupValueRaw === self::GROUP_VALUE_EMPTY_SENTINEL ? '' : (string) $groupValueRaw;
        }

        $tableFilterActive = $playerFilterActive || $thresholdRules !== [] || $groupFilterActive;

        $heatRulesForResponse = $dataSourceUpload->heat_rules;
        if (! is_array($heatRulesForResponse)) {
            $heatRulesForResponse = [];
        }

        $browse = is_array($dataSourceUpload->dataset_browse_settings) ? $dataSourceUpload->dataset_browse_settings : null;
        $effectiveHeatMinPa = $this->effectiveHeatMinPaForTable($request, $browse);
        $liveHeatMinQuery = $this->heatMinPaQueryOrNull($request);
        $heatMinForSubset = $groupFilterActive ? $effectiveHeatMinPa : null;

        $colCount = count($fileHeaders);
        $rawRowsForGroupScopedHeatStats = null;
        if ($playerFilterActive && $groupFilterActive) {
            $rawRowsForGroupScopedHeatStats = [];
            foreach ($materialized['rows'] as $row) {
                $normalized = [];
                for ($i = 0; $i < $colCount; $i++) {
                    $normalized[] = isset($row[$i]) ? (string) $row[$i] : '';
                }
                $rawRowsForGroupScopedHeatStats[] = $normalized;
            }
        }

        $rawRows = [];
        $rawOrdinals = [];
        $ordinalCounter = 0;
        foreach ($materialized['rows'] as $row) {
            if ($filterTerms !== []) {
                $cell = (string) ($row[$playerIdx] ?? '');
                if (! $this->csvRowMatchesPlayerTerms($cell, $filterTerms, $useExactPlayerMatch)) {
                    continue;
                }
            }
            $currentOrdinal = $ordinalCounter;
            $ordinalCounter++;
            $normalized = [];
            for ($i = 0; $i < $colCount; $i++) {
                $normalized[] = isset($row[$i]) ? (string) $row[$i] : '';
            }
            $rawRows[] = $normalized;
            $rawOrdinals[] = $currentOrdinal;
        }

        $page = (int) $request->query('page', 1);
        [$headers, $rows, $ordinals, $page, $lastPage, $totalRows, $subsetHeatStats] = $this->finalizePagedDisplayRows(
            $fileHeaders,
            $rawRows,
            $rawOrdinals,
            $playerIdx,
            $order,
            $thresholdRules,
            $sortColumnIndex,
            $sortAscending,
            $page,
            $perPage,
            $groupFilterActive ? $heatRulesForResponse : null,
            $groupFilterActive ? $groupColumnIndex : null,
            $groupFilterActive ? $groupValueMatch : null,
            $heatMinForSubset,
            $rawRowsForGroupScopedHeatStats
        );
        if ($subsetHeatStats !== null) {
            $heatColumnStatsOut = $subsetHeatStats;
        } elseif ($liveHeatMinQuery !== null && $this->heatRulesAreEnabled($dataSourceUpload)) {
            $computed = $this->computeHeatColumnStatsForUpload($dataSourceUpload, $liveHeatMinQuery);
            $heatColumnStatsOut = $computed === [] ? (object) [] : $computed;
        } else {
            $heatColumnStatsOut = $dataSourceUpload->heat_column_stats ?? (object) [];
        }

        $from = $totalRows === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $to = min($page * $perPage, $totalRows);

        $heatRowPaOk = $this->heatRowPaOkFlags($effectiveHeatMinPa, $headers, $rows);

        return response()->json([
            'headers' => $headers,
            'rows' => $rows,
            'row_ordinals' => $ordinals,
            'page' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'lastPage' => $lastPage,
            'from' => $from,
            'to' => $to,
            'original_filename' => $dataSourceUpload->original_filename,
            'filter_active' => $tableFilterActive,
            'column_order' => $order,
            'sort' => $sortActive ? [
                'column' => $sortColumnIndex,
                'direction' => $sortAscending ? 'asc' : 'desc',
            ] : null,
            'heat_rules' => $dataSourceUpload->heat_rules ?? (object) [],
            'heat_column_stats' => $heatColumnStatsOut ?? (object) [],
            'heat_pa_qualifier' => $this->heatPaQualifierForDisplay($headers, $effectiveHeatMinPa),
            'heat_row_pa_ok' => $heatRowPaOk,
            'group' => $groupColumnIndex !== null ? [
                'column' => $groupColumnIndex,
                'active' => $groupFilterActive,
            ] : null,
        ]);
    }

    private function careerPgMasterGroupColumnValues(Request $request, DataSourceUpload $dataSourceUpload): JsonResponse
    {
        $materialized = $this->materializeCareerPgData($dataSourceUpload);
        if ($materialized === null) {
            return response()->json(['values' => []]);
        }

        $fileHeaders = $materialized['headers'];
        $playerIdx = DataSourceCsvHeaders::playerColumnIndex($fileHeaders);
        $groupColRaw = $request->query('group_column');
        if ($groupColRaw === null || $groupColRaw === '' || ! is_numeric($groupColRaw)) {
            return response()->json(['values' => []]);
        }
        $groupColumnIndex = (int) $groupColRaw;
        if ($groupColumnIndex < 0 || $groupColumnIndex >= count($fileHeaders)) {
            return response()->json(['values' => []]);
        }

        $playersQuery = $request->query('players');
        /** @var list<string> $playerTerms */
        $playerTerms = [];
        if (is_array($playersQuery)) {
            foreach ($playersQuery as $p) {
                $s = trim((string) $p);
                if ($s !== '') {
                    $playerTerms[] = $s;
                }
            }
            $playerTerms = array_values(array_unique($playerTerms));
        } elseif ($playersQuery !== null && (string) $playersQuery !== '') {
            $playerTerms = [trim((string) $playersQuery)];
        }

        $useExactPlayerMatch = $playerTerms !== [];
        $filterTerms = $useExactPlayerMatch ? $playerTerms : [];
        $playerFilterActive = $filterTerms !== [];

        $order = $this->normalizeColumnOrder($dataSourceUpload->column_order, count($fileHeaders));

        $rawRows = [];
        foreach ($materialized['rows'] as $row) {
            if ($playerFilterActive) {
                $cell = (string) ($row[$playerIdx] ?? '');
                if (! $this->csvRowMatchesPlayerTerms($cell, $filterTerms, $useExactPlayerMatch)) {
                    continue;
                }
            }
            $colCount = count($fileHeaders);
            $normalized = [];
            for ($i = 0; $i < $colCount; $i++) {
                $normalized[] = isset($row[$i]) ? (string) $row[$i] : '';
            }
            $rawRows[] = $normalized;
        }

        [$dispHeaders, $dispRows] = $this->reorderPlayerFirst($fileHeaders, $rawRows, $playerIdx);
        [$dispHeaders, $dispRows] = $this->applyColumnOrderPermutation($dispHeaders, $dispRows, $order);

        if ($groupColumnIndex < 0 || $groupColumnIndex >= count($dispHeaders)) {
            return response()->json(['values' => []]);
        }

        /** @var array<string, true> $seen */
        $seen = [];
        foreach ($dispRows as $row) {
            $cell = trim((string) ($row[$groupColumnIndex] ?? ''));
            $seen[$cell] = true;
        }
        $values = array_keys($seen);
        usort($values, function (string $a, string $b): int {
            if ($a === '' && $b !== '') {
                return 1;
            }
            if ($a !== '' && $b === '') {
                return -1;
            }

            return strnatcasecmp($a, $b);
        });

        return response()->json(['values' => array_values($values)]);
    }

    public function updateSettings(UpdateDataSourceUploadSettingsRequest $request, DataSourceUpload $dataSourceUpload): JsonResponse
    {
        if ($request->has('column_order')) {
            /** @var list<int|string> $order */
            $order = $request->input('column_order');
            $dataSourceUpload->column_order = array_map(static fn ($v) => (int) $v, $order);
        }

        if ($request->has('heat_rules')) {
            /** @var array<string, mixed> $rawRules */
            $rawRules = $request->input('heat_rules', []);
            $normalized = [];
            foreach ($rawRules as $name => $rule) {
                if (! is_array($rule)) {
                    continue;
                }
                $normalized[(string) $name] = [
                    'enabled' => (bool) ($rule['enabled'] ?? false),
                    'higher_is_better' => (bool) ($rule['higher_is_better'] ?? true),
                ];
            }
            $dataSourceUpload->heat_rules = $normalized === [] ? null : $normalized;
        }

        if ($request->has('dataset_browse_settings')) {
            /** @var array<string, mixed> $rawBrowse */
            $rawBrowse = $request->input('dataset_browse_settings', []);
            $normalizedBrowse = $this->normalizeDatasetBrowseSettings($rawBrowse, count($dataSourceUpload->header_row));
            $dataSourceUpload->dataset_browse_settings = $normalizedBrowse;
        }

        if ($request->has('hs_profile_feed_slots') && ! $dataSourceUpload->isCareerPgMaster()) {
            /** @var list<mixed> $rawSlots */
            $rawSlots = $request->input('hs_profile_feed_slots', []);
            $slots = [];
            foreach ($rawSlots as $s) {
                if (is_string($s) && $s !== '') {
                    $slots[] = $s;
                }
            }
            $slots = array_values(array_unique($slots));
            $dataSourceUpload->hs_profile_feed_slots = $slots === [] ? null : $slots;

            if ($slots !== []) {
                $others = DataSourceUpload::query()
                    ->where('user_id', $dataSourceUpload->user_id)
                    ->whereKeyNot($dataSourceUpload->id)
                    ->get();
                foreach ($others as $other) {
                    $cur = $other->hs_profile_feed_slots;
                    if (! is_array($cur) || $cur === []) {
                        continue;
                    }
                    $next = array_values(array_diff($cur, $slots));
                    $other->hs_profile_feed_slots = $next === [] ? null : $next;
                    $other->save();
                }
            }
        }

        if ($dataSourceUpload->isCareerPgMaster()) {
            $dataSourceUpload->hs_profile_feed_slots = null;
        }

        $dataSourceUpload->save();

        if ($request->has('heat_rules')
            || ($request->has('dataset_browse_settings') && $this->heatRulesAreEnabled($dataSourceUpload))) {
            $this->recomputeHeatColumnStats($dataSourceUpload);
        }

        $response = ['ok' => true];

        if ($request->has('dataset_browse_settings')) {
            $response['dataset_browse_settings'] = $dataSourceUpload->dataset_browse_settings;
        }

        if ($request->has('hs_profile_feed_slots')) {
            $allForSlots = DataSourceUpload::query()
                ->where('user_id', $dataSourceUpload->user_id)
                ->orderBy('id')
                ->get(['id', 'hs_profile_feed_slots', 'upload_kind', 'career_pg_source_upload_id']);

            $response['hs_profile_feed_assignments'] = $allForSlots
                ->map(static function (DataSourceUpload $u) use ($allForSlots): array {
                    return [
                        'id' => (int) $u->id,
                        'hs_profile_feed_slots' => $u->resolvedHsProfileFeedSlotsForUi($allForSlots),
                    ];
                })
                ->values()
                ->all();
        }

        return response()->json($response);
    }

    public function storeRow(AppendDataSourceRowRequest $request, DataSourceUpload $dataSourceUpload): JsonResponse
    {
        if ($dataSourceUpload->isCareerPgMaster()) {
            throw ValidationException::withMessages([
                'cells' => [__('This dataset is read-only.')],
            ]);
        }

        $absolutePath = Storage::disk($dataSourceUpload->disk)->path($dataSourceUpload->path);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        $expectedN = count($dataSourceUpload->header_row);
        if ($expectedN === 0) {
            throw ValidationException::withMessages([
                'cells' => [__('This dataset has no columns.')],
            ]);
        }

        /** @var list<mixed> $rawCells */
        $rawCells = $request->input('cells', []);
        $fileRow = [];
        for ($i = 0; $i < $expectedN; $i++) {
            $v = $rawCells[$i] ?? null;
            $fileRow[] = $v === null || $v === '' ? '' : (string) $v;
        }

        [$diskHeader, $rows] = $this->readFullCsvData($absolutePath);
        if (count($diskHeader) !== $expectedN) {
            throw ValidationException::withMessages([
                'cells' => [__('CSV column count does not match this dataset.')],
            ]);
        }

        $rows[] = $fileRow;
        $this->writeFullCsv($absolutePath, $diskHeader, $rows);

        $stats = $this->csvUploadStats($absolutePath);
        $dataSourceUpload->row_count = $stats['row_count'];
        $dataSourceUpload->save();
        $this->recomputeHeatColumnStats($dataSourceUpload);
        CareerPgMasterUploadService::syncForUser($request->user());

        $perPage = 50;
        $lastPage = max(1, (int) ceil(max(1, $dataSourceUpload->row_count) / $perPage));

        return response()->json([
            'ok' => true,
            'row_count' => $dataSourceUpload->row_count,
            'lastPage' => $lastPage,
        ]);
    }

    public function updateRow(DataSourceRowUpdateRequest $request, DataSourceUpload $dataSourceUpload, int $ordinal): JsonResponse
    {
        if ($dataSourceUpload->isCareerPgMaster()) {
            throw ValidationException::withMessages([
                'player' => [__('This dataset is read-only.')],
            ]);
        }

        $absolutePath = Storage::disk($dataSourceUpload->disk)->path($dataSourceUpload->path);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        $playerIdx = DataSourceCsvHeaders::playerColumnIndex($dataSourceUpload->header_row);
        $this->updateCsvPlayerByOrdinal($absolutePath, $playerIdx, $ordinal, $request->validated('player'));
        CareerPgMasterUploadService::syncForUser($request->user());

        return response()->json(['ok' => true]);
    }

    public function destroyRow(Request $request, DataSourceUpload $dataSourceUpload, int $ordinal): JsonResponse
    {
        abort_unless($dataSourceUpload->user_id === $request->user()->id, 404);

        if ($dataSourceUpload->isCareerPgMaster()) {
            throw ValidationException::withMessages([
                'ordinal' => [__('This dataset is read-only.')],
            ]);
        }

        $absolutePath = Storage::disk($dataSourceUpload->disk)->path($dataSourceUpload->path);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        $this->deleteCsvRowByOrdinal($absolutePath, $ordinal);
        $stats = $this->csvUploadStats($absolutePath);
        $dataSourceUpload->row_count = $stats['row_count'];
        $dataSourceUpload->save();
        $this->recomputeHeatColumnStats($dataSourceUpload);
        CareerPgMasterUploadService::syncForUser($request->user());

        return response()->json(['ok' => true, 'row_count' => $dataSourceUpload->row_count]);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function reorderPlayerFirst(array $headers, array $rows, int $playerIdx): array
    {
        if ($headers === [] || $playerIdx <= 0) {
            return [$headers, $rows];
        }

        $playerIdx = min($playerIdx, count($headers) - 1);
        if ($playerIdx === 0) {
            return [$headers, $rows];
        }

        $newHeaders = [];
        $newHeaders[] = $headers[$playerIdx];
        foreach ($headers as $i => $h) {
            if ($i !== $playerIdx) {
                $newHeaders[] = $h;
            }
        }

        $newRows = [];
        foreach ($rows as $row) {
            $playerVal = $row[$playerIdx] ?? '';
            $newRow = [$playerVal];
            foreach ($row as $i => $cell) {
                if ($i !== $playerIdx) {
                    $newRow[] = $cell;
                }
            }
            $newRows[] = $newRow;
        }

        return [$newHeaders, $newRows];
    }

    /**
     * @param  list<int>|null  $order
     * @return list<int>
     */
    private function normalizeColumnOrder(?array $order, int $n): array
    {
        if ($n === 0) {
            return [];
        }
        if (! is_array($order) || count($order) !== $n) {
            return range(0, $n - 1);
        }
        $sorted = array_values($order);
        sort($sorted);
        if ($sorted !== range(0, $n - 1)) {
            return range(0, $n - 1);
        }

        return array_map(static fn ($v) => (int) $v, $order);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     * @param  list<int>  $order
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function applyColumnOrderPermutation(array $headers, array $rows, array $order): array
    {
        if ($order === [] || $headers === []) {
            return [$headers, $rows];
        }

        $newHeaders = [];
        foreach ($order as $i) {
            $newHeaders[] = $headers[$i] ?? '';
        }
        $newRows = [];
        foreach ($rows as $row) {
            $newRow = [];
            foreach ($order as $i) {
                $newRow[] = $row[$i] ?? '';
            }
            $newRows[] = $newRow;
        }

        return [$newHeaders, $newRows];
    }

    /**
     * @return array<string, array{min: float, max: float, median: float}>
     */
    private function computeHeatColumnStatsForUpload(DataSourceUpload $upload, ?float $heatMinPa): array
    {
        $rules = $upload->heat_rules;
        if (! is_array($rules) || $rules === []) {
            return [];
        }

        /** @var list<string> $headerRow */
        $headerRow = array_map(static fn ($h) => is_string($h) ? $h : '', $upload->header_row ?? []);
        $careerGridRows = null;
        if ($upload->isCareerPgMaster()) {
            $mat = $this->materializeCareerPgData($upload);
            if ($mat === null || $mat['rows'] === []) {
                return [];
            }
            $headerRow = $mat['headers'];
            $careerGridRows = $mat['rows'];
        }

        $indexes = [];
        foreach ($rules as $name => $rule) {
            if (! is_array($rule) || ! ($rule['enabled'] ?? false)) {
                continue;
            }
            foreach ($headerRow as $i => $h) {
                if ((string) $h === (string) $name) {
                    $indexes[(string) $name] = (int) $i;

                    break;
                }
            }
        }

        if ($indexes === []) {
            return [];
        }

        $paCol = null;
        $paMinActive = null;
        if ($heatMinPa !== null) {
            $paCol = DataSourceCsvHeaders::plateAppearancesColumnIndex($headerRow);
            if ($paCol !== null) {
                $paMinActive = $heatMinPa;
            }
        }

        /** @var array<string, list<float>> $valueLists */
        $valueLists = [];
        foreach (array_keys($indexes) as $name) {
            $valueLists[$name] = [];
        }

        if ($careerGridRows !== null) {
            foreach ($careerGridRows as $row) {
                if ($paMinActive !== null) {
                    $paRaw = (string) ($row[$paCol] ?? '');
                    $paVal = $this->parseNumericForHeat($paRaw);
                    if ($paVal === null || $paVal < $paMinActive) {
                        continue;
                    }
                }
                foreach ($indexes as $name => $idx) {
                    $raw = (string) ($row[$idx] ?? '');
                    $val = $this->parseNumericForHeat($raw);
                    if ($val === null) {
                        continue;
                    }
                    $valueLists[$name][] = (float) $val;
                }
            }
        } else {
            $path = Storage::disk($upload->disk)->path($upload->path);
            $handle = fopen($path, 'r');
            if ($handle === false) {
                return [];
            }

            try {
                fgetcsv($handle);
                while (($row = fgetcsv($handle)) !== false) {
                    if ($this->isBlankCsvRow($row)) {
                        continue;
                    }
                    if ($paMinActive !== null) {
                        $paRaw = (string) ($row[$paCol] ?? '');
                        $paVal = $this->parseNumericForHeat($paRaw);
                        if ($paVal === null || $paVal < $paMinActive) {
                            continue;
                        }
                    }
                    foreach ($indexes as $name => $idx) {
                        $raw = (string) ($row[$idx] ?? '');
                        $val = $this->parseNumericForHeat($raw);
                        if ($val === null) {
                            continue;
                        }
                        $valueLists[$name][] = (float) $val;
                    }
                }
            } finally {
                fclose($handle);
            }
        }

        $stats = [];
        foreach ($indexes as $name => $_) {
            $vals = $valueLists[$name];
            if ($vals === []) {
                continue;
            }
            sort($vals, SORT_NUMERIC);
            $n = count($vals);
            $minF = (float) $vals[0];
            $maxF = (float) $vals[$n - 1];
            if (abs($maxF - $minF) < 1.0e-6) {
                continue;
            }
            if ($n % 2 === 1) {
                $median = (float) $vals[intdiv($n, 2)];
            } else {
                $mid = intdiv($n, 2);
                $median = ((float) $vals[$mid - 1] + (float) $vals[$mid]) / 2;
            }
            $stats[$name] = [
                'min' => $minF,
                'max' => $maxF,
                'median' => $median,
            ];
        }

        return $stats;
    }

    private function recomputeHeatColumnStats(DataSourceUpload $upload): void
    {
        $rules = $upload->heat_rules;
        if (! is_array($rules) || $rules === []) {
            $upload->heat_column_stats = null;
            $upload->save();

            return;
        }

        $paMin = $this->heatMinPaFromBrowse(is_array($upload->dataset_browse_settings) ? $upload->dataset_browse_settings : null);
        $stats = $this->computeHeatColumnStatsForUpload($upload, $paMin);
        $upload->heat_column_stats = $stats === [] ? null : $stats;
        $upload->save();
    }

    private function heatMinPaQueryOrNull(Request $request): ?float
    {
        if (! $request->has('heat_min_pa')) {
            return null;
        }
        $v = $request->query('heat_min_pa');
        if ($v === null || $v === '' || ! is_numeric($v)) {
            return null;
        }
        $f = (float) $v;

        return $f >= 0 ? $f : null;
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
     * Query param (draft) overrides saved browse for one request.
     *
     * @param  array<string, mixed>|null  $browse
     */
    private function effectiveHeatMinPaForTable(Request $request, ?array $browse): ?float
    {
        $q = $this->heatMinPaQueryOrNull($request);
        if ($q !== null) {
            return $q;
        }

        return $this->heatMinPaFromBrowse($browse);
    }

    /**
     * @return array{min: float|null, column_index: int|null}
     */
    private function heatPaQualifierForDisplay(array $dispHeaders, ?float $minPa): array
    {
        if ($minPa === null) {
            return ['min' => null, 'column_index' => null];
        }
        $idx = DataSourceCsvHeaders::plateAppearancesColumnIndex($dispHeaders);
        if ($idx === null) {
            return ['min' => null, 'column_index' => null];
        }

        return ['min' => $minPa, 'column_index' => $idx];
    }

    /**
     * One flag per displayed row: whether heat shading may apply for that row (PA meets minimum).
     * Null when no PA minimum is active — client should not use this array to gate.
     *
     * @param  list<string>  $dispHeaders
     * @param  list<list<string>>  $pageRows
     * @return list<bool>|null
     */
    private function heatRowPaOkFlags(?float $minPa, array $dispHeaders, array $pageRows): ?array
    {
        if ($minPa === null) {
            return null;
        }

        $paIdx = DataSourceCsvHeaders::plateAppearancesColumnIndex($dispHeaders);
        $out = [];
        if ($paIdx === null) {
            foreach ($pageRows as $_) {
                $out[] = false;
            }

            return $out;
        }

        foreach ($pageRows as $row) {
            $raw = (string) ($row[$paIdx] ?? '');
            $val = $this->parseNumericForHeat($raw);
            $out[] = $val !== null && $val >= $minPa;
        }

        return $out;
    }

    private function heatRulesAreEnabled(DataSourceUpload $upload): bool
    {
        $rules = $upload->heat_rules;
        if (! is_array($rules) || $rules === []) {
            return false;
        }
        foreach ($rules as $rule) {
            if (is_array($rule) && ($rule['enabled'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    private function parseNumericForHeat(string $raw): ?float
    {
        $t = str_replace([',', '%', ' '], '', trim($raw));
        if ($t === '' || ! is_numeric($t)) {
            return null;
        }

        return (float) $t;
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function readFullCsvData(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('unreadable');
        }

        try {
            $header = fgetcsv($handle);
            if ($header === false) {
                return [[], []];
            }

            /** @var list<string> $headerNorm */
            $headerNorm = array_map(static function ($c): string {
                if (! is_string($c)) {
                    return (string) $c;
                }

                return (string) preg_replace('/^\xEF\xBB\xBF|\x{FEFF}/u', '', trim($c));
            }, $header);
            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }

            return [$headerNorm, $rows];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string>  $header
     * @param  list<list<string>>  $rows
     */
    private function writeFullCsv(string $absolutePath, array $header, array $rows): void
    {
        $tmp = $absolutePath.'.tmp';
        $h = fopen($tmp, 'w');
        if ($h === false) {
            throw new \RuntimeException('write');
        }

        fputcsv($h, $header);
        foreach ($rows as $row) {
            fputcsv($h, $row);
        }
        fclose($h);
        if (! rename($tmp, $absolutePath)) {
            throw new \RuntimeException('rename');
        }
    }

    private function deleteCsvRowByOrdinal(string $absolutePath, int $ordinal): void
    {
        [$header, $rows] = $this->readFullCsvData($absolutePath);
        if ($header === []) {
            abort(404);
        }

        $seen = 0;
        $newRows = [];
        $deleted = false;
        foreach ($rows as $row) {
            if ($this->isBlankCsvRow($row)) {
                $newRows[] = $row;

                continue;
            }
            if ($seen === $ordinal) {
                $deleted = true;
                $seen++;

                continue;
            }
            $newRows[] = $row;
            $seen++;
        }

        if (! $deleted) {
            abort(404);
        }

        $this->writeFullCsv($absolutePath, $header, $newRows);
    }

    private function updateCsvPlayerByOrdinal(string $absolutePath, int $playerColIdx, int $ordinal, string $player): void
    {
        [$header, $rows] = $this->readFullCsvData($absolutePath);
        if ($header === []) {
            abort(404);
        }

        $seen = 0;
        $updated = false;
        foreach ($rows as $i => $row) {
            if ($this->isBlankCsvRow($row)) {
                continue;
            }
            if ($seen === $ordinal) {
                while (count($rows[$i]) <= $playerColIdx) {
                    $rows[$i][] = '';
                }
                $rows[$i][$playerColIdx] = $player;
                $updated = true;

                break;
            }
            $seen++;
        }

        if (! $updated) {
            abort(404);
        }

        $this->writeFullCsv($absolutePath, $header, $rows);
    }

    /**
     * @param  list<string>  $filterTerms
     * @return array{0: int, 1: list<list<string>>, 2: int, 3: int, 4: list<int>}
     */
    private function readCsvFilteredPage(
        string $absolutePath,
        array $expectedHeader,
        int $perPage,
        int $page,
        array $filterTerms,
        bool $exactMatch,
        int $playerColumnIndex
    ): array {
        $colCount = count($expectedHeader);
        $playerColumnIndex = max(0, min($playerColumnIndex, max(0, $colCount - 1)));

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('unreadable');
        }

        try {
            $discardedHeader = fgetcsv($handle);
            if ($discardedHeader === false) {
                return [0, [], 1, 1, []];
            }

            $totalMatches = 0;
            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isBlankCsvRow($row)) {
                    continue;
                }
                $cell = (string) ($row[$playerColumnIndex] ?? '');
                if (! $this->csvRowMatchesPlayerTerms($cell, $filterTerms, $exactMatch)) {
                    continue;
                }
                $totalMatches++;
            }
        } finally {
            fclose($handle);
        }

        $lastPage = max(1, (int) ceil($totalMatches / $perPage));
        $page = min(max(1, $page), $lastPage);

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('unreadable');
        }

        try {
            fgetcsv($handle);
            $skip = ($page - 1) * $perPage;
            $skipped = 0;
            $rows = [];
            $ordinals = [];
            $ordinalCounter = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isBlankCsvRow($row)) {
                    continue;
                }
                $currentOrdinal = $ordinalCounter;
                $ordinalCounter++;
                $cell = (string) ($row[$playerColumnIndex] ?? '');
                if (! $this->csvRowMatchesPlayerTerms($cell, $filterTerms, $exactMatch)) {
                    continue;
                }
                if ($skipped < $skip) {
                    $skipped++;

                    continue;
                }
                if (count($rows) >= $perPage) {
                    break;
                }
                $normalized = [];
                for ($i = 0; $i < $colCount; $i++) {
                    $normalized[] = isset($row[$i]) ? (string) $row[$i] : '';
                }
                $rows[] = $normalized;
                $ordinals[] = $currentOrdinal;
            }

            return [$totalMatches, $rows, $page, $lastPage, $ordinals];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string>  $terms
     */
    private function csvRowMatchesPlayerTerms(string $cell, array $terms, bool $exactMatch): bool
    {
        foreach ($terms as $term) {
            $t = trim((string) $term);
            if ($t === '') {
                continue;
            }
            if ($exactMatch) {
                if (strcasecmp(trim($cell), $t) === 0) {
                    return true;
                }
            } elseif (str_contains(strtolower($cell), strtolower($t))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $filterTerms
     * @return array{0: list<list<string>>, 1: list<int>}
     */
    private function collectCsvRowsWithOrdinals(
        string $absolutePath,
        int $colCount,
        ?int $filterPlayerColumnIndex,
        array $filterTerms,
        bool $exactMatch
    ): array {
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('unreadable');
        }

        $colCount = max(1, $colCount);
        $filterPlayerColumnIndex = $filterPlayerColumnIndex === null
            ? 0
            : max(0, min($filterPlayerColumnIndex, $colCount - 1));

        try {
            if (fgetcsv($handle) === false) {
                return [[], []];
            }

            $allRows = [];
            $allOrdinals = [];
            $ordinalCounter = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isBlankCsvRow($row)) {
                    continue;
                }
                $currentOrdinal = $ordinalCounter;
                $ordinalCounter++;
                if ($filterTerms !== []) {
                    $cell = (string) ($row[$filterPlayerColumnIndex] ?? '');
                    if (! $this->csvRowMatchesPlayerTerms($cell, $filterTerms, $exactMatch)) {
                        continue;
                    }
                }
                $normalized = [];
                for ($i = 0; $i < $colCount; $i++) {
                    $normalized[] = isset($row[$i]) ? (string) $row[$i] : '';
                }
                $allRows[] = $normalized;
                $allOrdinals[] = $currentOrdinal;
            }

            return [$allRows, $allOrdinals];
        } finally {
            fclose($handle);
        }
    }

    private function compareDataSourceSortCells(string $a, string $b): int
    {
        $ta = trim($a);
        $tb = trim($b);
        if ($ta === '' && $tb === '') {
            return 0;
        }
        if ($ta === '') {
            return -1;
        }
        if ($tb === '') {
            return 1;
        }

        $cleanA = str_replace([',', '%', ' '], '', $ta);
        $cleanB = str_replace([',', '%', ' '], '', $tb);
        $na = ($cleanA !== '' && is_numeric($cleanA)) ? (float) $cleanA : null;
        $nb = ($cleanB !== '' && is_numeric($cleanB)) ? (float) $cleanB : null;
        if ($na !== null && $nb !== null) {
            return $na <=> $nb;
        }

        return strnatcasecmp($ta, $tb);
    }

    /**
     * @param  list<list<string>>  $dispRows
     * @param  list<array{col: int, min: float|null, max: float|null}>  $thresholdRules
     * @return list<list<string>>
     */
    private function filterRowsByGroupAndThresholds(
        array $dispHeaders,
        array $dispRows,
        ?int $groupDisplayColumn,
        ?string $groupDisplayValue,
        array $thresholdRules,
    ): array {
        if ($groupDisplayColumn !== null && $groupDisplayValue !== null
            && $groupDisplayColumn >= 0 && $groupDisplayColumn < count($dispHeaders)) {
            $filteredRows = [];
            foreach ($dispRows as $row) {
                $cell = trim((string) ($row[$groupDisplayColumn] ?? ''));
                if (strcasecmp($cell, $groupDisplayValue) === 0) {
                    $filteredRows[] = $row;
                }
            }
            $dispRows = $filteredRows;
        }

        if ($thresholdRules !== []) {
            $filteredRows = [];
            foreach ($dispRows as $row) {
                if ($this->rowPassesColumnThresholds($row, $thresholdRules)) {
                    $filteredRows[] = $row;
                }
            }
            $dispRows = $filteredRows;
        }

        return $dispRows;
    }

    /**
     * @param  list<array{col: int, min: float|null, max: float|null}>  $thresholdRules
     * @param  list<string>  $fileHeaders
     * @param  list<list<string>>  $rawRows
     * @param  list<int>  $rawOrdinals
     * @param  list<int>  $columnOrder
     * @param  array<string, mixed>|null  $heatRulesForSubset
     * @param  list<list<string>>|null  $rawRowsForGroupScopedHeatStats  Full file rows (no player filter) for heat min/max when group + player filters are both active
     * @return array{0: list<string>, 1: list<list<string>>, 2: list<int>, 3: int, 4: int, 5: int, 6: array<string, array{min: float, max: float, median: float}>|null}
     */
    private function finalizePagedDisplayRows(
        array $fileHeaders,
        array $rawRows,
        array $rawOrdinals,
        int $playerIdx,
        array $columnOrder,
        array $thresholdRules,
        ?int $sortColumnIndex,
        bool $sortAscending,
        int $page,
        int $perPage,
        ?array $heatRulesForSubset = null,
        ?int $groupDisplayColumn = null,
        ?string $groupDisplayValue = null,
        ?float $heatMinPaForSubset = null,
        ?array $rawRowsForGroupScopedHeatStats = null,
    ): array {
        [$dispHeaders, $dispRows] = $this->reorderPlayerFirst($fileHeaders, $rawRows, $playerIdx);
        [$dispHeaders, $dispRows] = $this->applyColumnOrderPermutation($dispHeaders, $dispRows, $columnOrder);

        $ordinals = $rawOrdinals;

        if ($groupDisplayColumn !== null && $groupDisplayValue !== null
            && $groupDisplayColumn >= 0 && $groupDisplayColumn < count($dispHeaders)) {
            $filteredRows = [];
            $filteredOrdinals = [];
            foreach ($dispRows as $i => $row) {
                $cell = trim((string) ($row[$groupDisplayColumn] ?? ''));
                if (strcasecmp($cell, $groupDisplayValue) === 0) {
                    $filteredRows[] = $row;
                    $filteredOrdinals[] = $ordinals[$i];
                }
            }
            $dispRows = $filteredRows;
            $ordinals = $filteredOrdinals;
        }

        if ($thresholdRules !== []) {
            $filteredRows = [];
            $filteredOrdinals = [];
            foreach ($dispRows as $i => $row) {
                if ($this->rowPassesColumnThresholds($row, $thresholdRules)) {
                    $filteredRows[] = $row;
                    $filteredOrdinals[] = $ordinals[$i];
                }
            }
            $dispRows = $filteredRows;
            $ordinals = $filteredOrdinals;
        }

        $subsetHeatStats = null;
        if (is_array($heatRulesForSubset) && $heatRulesForSubset !== []) {
            $paIdx = null;
            $paMin = $heatMinPaForSubset;
            if ($paMin !== null) {
                $paIdx = DataSourceCsvHeaders::plateAppearancesColumnIndex($dispHeaders);
                if ($paIdx === null) {
                    $paMin = null;
                }
            }
            $rowsForHeatStats = $dispRows;
            if (is_array($rawRowsForGroupScopedHeatStats) && $rawRowsForGroupScopedHeatStats !== []) {
                [$heatHeaders, $heatRowsWide] = $this->reorderPlayerFirst($fileHeaders, $rawRowsForGroupScopedHeatStats, $playerIdx);
                [$heatHeaders, $heatRowsWide] = $this->applyColumnOrderPermutation($heatHeaders, $heatRowsWide, $columnOrder);
                $rowsForHeatStats = $this->filterRowsByGroupAndThresholds(
                    $heatHeaders,
                    $heatRowsWide,
                    $groupDisplayColumn,
                    $groupDisplayValue,
                    $thresholdRules,
                );
            }
            $subsetHeatStats = DataSourceHeatColumnStats::compute($dispHeaders, $rowsForHeatStats, $heatRulesForSubset, $paIdx, $paMin);
        }

        $totalRows = count($dispRows);
        $lastPage = max(1, (int) ceil(max(1, $totalRows) / $perPage));
        $page = min(max(1, $page), $lastPage);

        if ($sortColumnIndex !== null
            && $sortColumnIndex >= 0
            && $sortColumnIndex < count($dispHeaders)
            && $totalRows > 0) {
            $pairs = [];
            foreach ($dispRows as $i => $row) {
                $pairs[] = ['row' => $row, 'ord' => $ordinals[$i]];
            }
            usort($pairs, function (array $x, array $y) use ($sortColumnIndex, $sortAscending): int {
                $c = $this->compareDataSourceSortCells($x['row'][$sortColumnIndex] ?? '', $y['row'][$sortColumnIndex] ?? '');
                if ($c === 0) {
                    return $x['ord'] <=> $y['ord'];
                }

                return $sortAscending ? $c : -$c;
            });
            $dispRows = array_map(static fn (array $p): array => $p['row'], $pairs);
            $ordinalsAligned = array_map(static fn (array $p): int => $p['ord'], $pairs);
        } else {
            $ordinalsAligned = $ordinals;
        }

        $offset = ($page - 1) * $perPage;
        $pageRows = array_slice($dispRows, $offset, $perPage);
        $pageOrdinals = array_slice($ordinalsAligned, $offset, $perPage);

        return [$dispHeaders, $pageRows, $pageOrdinals, $page, $lastPage, $totalRows, $subsetHeatStats];
    }

    /**
     * @return list<array{col: int, min: float|null, max: float|null}>
     */
    /**
     * @param  array<string, mixed>  $raw
     * @return array{players: list<string>, column_thresholds: list<array{col: int, min?: float, max?: float}>, group_column: int|null, group_value: string|null}|null
     */
    private function normalizeDatasetBrowseSettings(array $raw, int $columnCount): ?array
    {
        if ($columnCount <= 0) {
            return null;
        }

        $players = [];
        if (isset($raw['players']) && is_array($raw['players'])) {
            foreach ($raw['players'] as $p) {
                $s = trim((string) $p);
                if ($s !== '') {
                    $players[] = $s;
                }
            }
            $players = array_values(array_unique($players));
        }

        $thresholds = [];
        if (isset($raw['column_thresholds']) && is_array($raw['column_thresholds'])) {
            foreach ($raw['column_thresholds'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $col = $item['col'] ?? $item['column'] ?? null;
                if (! is_numeric($col)) {
                    continue;
                }
                $col = (int) $col;
                if ($col < 0 || $col >= $columnCount) {
                    continue;
                }
                $minF = null;
                $maxF = null;
                $minRaw = $item['min'] ?? null;
                $maxRaw = $item['max'] ?? null;
                if ($minRaw !== null && $minRaw !== '' && is_numeric($minRaw)) {
                    $minF = (float) $minRaw;
                }
                if ($maxRaw !== null && $maxRaw !== '' && is_numeric($maxRaw)) {
                    $maxF = (float) $maxRaw;
                }
                if ($minF === null && $maxF === null) {
                    continue;
                }
                if ($minF !== null && $maxF !== null && $minF > $maxF) {
                    [$minF, $maxF] = [$maxF, $minF];
                }
                $entry = ['col' => $col];
                if ($minF !== null) {
                    $entry['min'] = $minF;
                }
                if ($maxF !== null) {
                    $entry['max'] = $maxF;
                }
                $thresholds[] = $entry;
            }
        }

        $groupColumn = null;
        if (isset($raw['group_column']) && $raw['group_column'] !== null && $raw['group_column'] !== '') {
            $g = (int) $raw['group_column'];
            if ($g >= 0 && $g < $columnCount) {
                $groupColumn = $g;
            }
        }

        $groupValue = null;
        if ($groupColumn !== null && array_key_exists('group_value', $raw)) {
            $gv = $raw['group_value'];
            $groupValue = $gv === null ? null : (string) $gv;
        }

        $heatMinPa = null;
        if (array_key_exists('heat_min_pa', $raw)) {
            $hm = $raw['heat_min_pa'];
            if ($hm !== null && $hm !== '' && is_numeric($hm)) {
                $v = (float) $hm;
                if ($v >= 0) {
                    $heatMinPa = $v;
                }
            }
        }

        $empty =
            $players === []
            && $thresholds === []
            && $groupColumn === null
            && $heatMinPa === null;

        if ($empty) {
            return null;
        }

        return [
            'players' => $players,
            'column_thresholds' => $thresholds,
            'group_column' => $groupColumn,
            'group_value' => $groupValue,
            'heat_min_pa' => $heatMinPa,
        ];
    }

    private function parseColumnThresholds(Request $request, int $columnCount): array
    {
        if ($columnCount <= 0) {
            return [];
        }

        $raw = $request->query('column_thresholds');
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return [];
            }
        } elseif (is_array($raw)) {
            $decoded = $raw;
        } else {
            return [];
        }

        $rules = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }
            $col = $item['col'] ?? $item['column'] ?? null;
            if (! is_numeric($col)) {
                continue;
            }
            $col = (int) $col;
            if ($col < 0 || $col >= $columnCount) {
                continue;
            }

            $minF = null;
            $maxF = null;
            $minRaw = $item['min'] ?? null;
            $maxRaw = $item['max'] ?? null;
            if ($minRaw !== null && $minRaw !== '' && is_numeric($minRaw)) {
                $minF = (float) $minRaw;
            }
            if ($maxRaw !== null && $maxRaw !== '' && is_numeric($maxRaw)) {
                $maxF = (float) $maxRaw;
            }
            if ($minF === null && $maxF === null) {
                continue;
            }
            if ($minF !== null && $maxF !== null && $minF > $maxF) {
                [$minF, $maxF] = [$maxF, $minF];
            }

            $rules[] = ['col' => $col, 'min' => $minF, 'max' => $maxF];
        }

        return $rules;
    }

    /**
     * @param  list<array{col: int, min: float|null, max: float|null}>  $rules
     */
    private function rowPassesColumnThresholds(array $displayRow, array $rules): bool
    {
        foreach ($rules as $r) {
            $col = $r['col'];
            $cell = trim((string) ($displayRow[$col] ?? ''));
            $clean = str_replace([',', '%'], '', $cell);
            if ($clean === '' || ! is_numeric($clean)) {
                return false;
            }
            $val = (float) $clean;
            if ($r['min'] !== null && $val < $r['min']) {
                return false;
            }
            if ($r['max'] !== null && $val > $r['max']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $expectedHeader
     * @return array{0: list<list<string>>, 1: list<int>}
     */
    private function readCsvDataPage(string $absolutePath, array $expectedHeader, int $page, int $perPage): array
    {
        $colCount = count($expectedHeader);
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('unreadable');
        }

        try {
            $discardedHeader = fgetcsv($handle);
            if ($discardedHeader === false) {
                return [[], []];
            }

            $skip = ($page - 1) * $perPage;
            $skipped = 0;
            $rows = [];
            $ordinals = [];
            $ordinalCounter = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isBlankCsvRow($row)) {
                    continue;
                }
                $currentOrdinal = $ordinalCounter;
                $ordinalCounter++;
                if ($skipped < $skip) {
                    $skipped++;

                    continue;
                }
                if (count($rows) >= $perPage) {
                    break;
                }
                $normalized = [];
                for ($i = 0; $i < $colCount; $i++) {
                    $normalized[] = isset($row[$i]) ? (string) $row[$i] : '';
                }
                $rows[] = $normalized;
                $ordinals[] = $currentOrdinal;
            }

            return [$rows, $ordinals];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array{header_row: list<string>, row_count: int}
     */
    private function csvUploadStats(string $absolutePath): array
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
                if ($this->isBlankCsvRow($row)) {
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
    private function isBlankCsvRow(array $row): bool
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
