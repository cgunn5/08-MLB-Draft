<?php

namespace App\Http\Controllers;

use App\Http\Requests\DataSourceRowUpdateRequest;
use App\Http\Requests\StoreDataSourceUploadRequest;
use App\Http\Requests\UpdateDataSourceUploadSettingsRequest;
use App\Models\DataSourceUpload;
use App\Support\DataSourceCsvHeaders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DataSourceController extends Controller
{
    public function index(): View
    {
        $uploads = DataSourceUpload::query()
            ->where('user_id', auth()->id())
            ->latest()
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

        $disk = Storage::disk($dataSourceUpload->disk);
        if ($dataSourceUpload->path !== '' && $disk->exists($dataSourceUpload->path)) {
            $disk->delete($dataSourceUpload->path);
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

        $needsFullMaterialize = $sortActive || $thresholdActive;

        $tableFilterActive = $playerFilterActive || $thresholdActive;

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
        } else {
            if (! $playerFilterActive) {
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
            [$headers, $rows, $ordinals, $page, $lastPage, $totalRows] = $this->finalizePagedDisplayRows(
                $headers,
                $rawRows,
                $rawOrdinals,
                $playerIdx,
                $order,
                $thresholdRules,
                $sortColumnIndex,
                $sortAscending,
                $page,
                $perPage
            );
        }

        $from = $totalRows === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $to = min($page * $perPage, $totalRows);

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
            'heat_column_stats' => $dataSourceUpload->heat_column_stats ?? (object) [],
        ]);
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

        if ($request->has('for_hs_ranger_traits')) {
            $on = $request->boolean('for_hs_ranger_traits');
            $dataSourceUpload->for_hs_ranger_traits = $on;
            if ($on) {
                DataSourceUpload::query()
                    ->where('user_id', $dataSourceUpload->user_id)
                    ->whereKeyNot($dataSourceUpload->id)
                    ->update(['for_hs_ranger_traits' => false]);
            }
        }

        $dataSourceUpload->save();

        if ($request->has('heat_rules')) {
            $this->recomputeHeatColumnStats($dataSourceUpload);
        }

        return response()->json([
            'ok' => true,
            'for_hs_ranger_traits' => (bool) $dataSourceUpload->for_hs_ranger_traits,
        ]);
    }

    public function updateRow(DataSourceRowUpdateRequest $request, DataSourceUpload $dataSourceUpload, int $ordinal): JsonResponse
    {
        $absolutePath = Storage::disk($dataSourceUpload->disk)->path($dataSourceUpload->path);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        $playerIdx = DataSourceCsvHeaders::playerColumnIndex($dataSourceUpload->header_row);
        $this->updateCsvPlayerByOrdinal($absolutePath, $playerIdx, $ordinal, $request->validated('player'));

        return response()->json(['ok' => true]);
    }

    public function destroyRow(Request $request, DataSourceUpload $dataSourceUpload, int $ordinal): JsonResponse
    {
        abort_unless($dataSourceUpload->user_id === $request->user()->id, 404);

        $absolutePath = Storage::disk($dataSourceUpload->disk)->path($dataSourceUpload->path);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        $this->deleteCsvRowByOrdinal($absolutePath, $ordinal);
        $stats = $this->csvUploadStats($absolutePath);
        $dataSourceUpload->row_count = $stats['row_count'];
        $dataSourceUpload->save();
        $this->recomputeHeatColumnStats($dataSourceUpload);

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

    private function recomputeHeatColumnStats(DataSourceUpload $upload): void
    {
        $rules = $upload->heat_rules;
        if (! is_array($rules) || $rules === []) {
            $upload->heat_column_stats = null;
            $upload->save();

            return;
        }

        $indexes = [];
        foreach ($rules as $name => $rule) {
            if (! is_array($rule) || ! ($rule['enabled'] ?? false)) {
                continue;
            }
            foreach ($upload->header_row as $i => $h) {
                if ((string) $h === (string) $name) {
                    $indexes[(string) $name] = (int) $i;

                    break;
                }
            }
        }

        if ($indexes === []) {
            $upload->heat_column_stats = null;
            $upload->save();

            return;
        }

        $path = Storage::disk($upload->disk)->path($upload->path);
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return;
        }

        fgetcsv($handle);
        /** @var array<string, list<float>> $valueLists */
        $valueLists = [];
        foreach (array_keys($indexes) as $name) {
            $valueLists[$name] = [];
        }

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isBlankCsvRow($row)) {
                continue;
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
        fclose($handle);

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

        $upload->heat_column_stats = $stats === [] ? null : $stats;
        $upload->save();
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

        $na = is_numeric($ta) ? (float) $ta : null;
        $nb = is_numeric($tb) ? (float) $tb : null;
        if ($na !== null && $nb !== null) {
            return $na <=> $nb;
        }

        return strnatcasecmp($ta, $tb);
    }

    /**
     * @param  list<array{col: int, min: float|null, max: float|null}>  $thresholdRules
     * @param  list<string>  $fileHeaders
     * @param  list<list<string>>  $rawRows
     * @param  list<int>  $rawOrdinals
     * @param  list<int>  $columnOrder
     * @return array{0: list<string>, 1: list<list<string>>, 2: list<int>, 3: int, 4: int, 5: int}
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
        int $perPage
    ): array {
        [$dispHeaders, $dispRows] = $this->reorderPlayerFirst($fileHeaders, $rawRows, $playerIdx);
        [$dispHeaders, $dispRows] = $this->applyColumnOrderPermutation($dispHeaders, $dispRows, $columnOrder);

        $ordinals = $rawOrdinals;

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

        return [$dispHeaders, $pageRows, $pageOrdinals, $page, $lastPage, $totalRows];
    }

    /**
     * @return list<array{col: int, min: float|null, max: float|null}>
     */
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
