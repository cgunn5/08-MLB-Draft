import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * RFC4180-style CSV parse (quoted fields, escaped quotes). Sufficient to match typical exports.
 *
 * @param {string} text
 * @return {string[][]}
 */
function parseCsvText(text) {
    const rows = [];
    let row = [];
    let field = '';
    let inQuotes = false;

    const pushField = () => {
        row.push(field);
        field = '';
    };

    const pushRow = () => {
        rows.push(row);
        row = [];
    };

    for (let i = 0; i < text.length; i++) {
        const c = text[i];
        if (inQuotes) {
            if (c === '"' && text[i + 1] === '"') {
                field += '"';
                i++;
            } else if (c === '"') {
                inQuotes = false;
            } else {
                field += c;
            }
        } else if (c === '"') {
            inQuotes = true;
        } else if (c === ',') {
            pushField();
        } else if (c === '\n') {
            pushField();
            pushRow();
        } else if (c === '\r') {
            // ignore
        } else {
            field += c;
        }
    }

    pushField();
    if (row.length > 1 || row[0] !== '') {
        pushRow();
    }

    return rows;
}

/**
 * @param {string[]} row
 */
function isBlankCsvRow(row) {
    if (!row || row.length === 0) {
        return true;
    }

    return row.every((cell) => String(cell ?? '').trim() === '');
}

document.addEventListener('alpine:init', () => {
    Alpine.data('csvUploadPreview', (config) => ({
        sourceName: config.oldName ?? '',
        maxPreviewRows: config.maxPreviewRows ?? 15,
        maxFileBytes: config.maxFileBytes ?? 10 * 1024 * 1024,
        previewHeaders: [],
        previewRows: [],
        previewError: '',
        previewNotice: '',
        fileLabel: '',

        get canSave() {
            return (
                this.sourceName.trim() !== '' &&
                this.previewHeaders.length > 0 &&
                this.previewError === '' &&
                this.fileLabel !== ''
            );
        },

        onFileChange(event) {
            const input = event.target;
            const file = input.files?.[0];
            this.previewError = '';
            this.previewNotice = '';
            this.previewHeaders = [];
            this.previewRows = [];
            this.fileLabel = '';

            if (!file) {
                return;
            }

            this.fileLabel = file.name;

            if (file.size > this.maxFileBytes) {
                this.previewError = 'File is too large (maximum 10 MB).';
                input.value = '';

                return;
            }

            const chunkLimit = file.size <= 2 * 1024 * 1024 ? file.size : 512 * 1024;
            const blob = file.slice(0, chunkLimit);

            blob
                .text()
                .then((text) => {
                    const rows = parseCsvText(text);
                    if (rows.length === 0) {
                        this.previewError = 'Could not read any rows from this file.';
                        input.value = '';

                        return;
                    }

                    const headers = rows[0].map((h) => String(h ?? '').trim());
                    if (headers.length === 0 || (headers.length === 1 && headers[0] === '')) {
                        this.previewError = 'The first row must contain column headers.';
                        input.value = '';

                        return;
                    }

                    this.previewHeaders = headers;
                    const body = rows.slice(1).filter((r) => !isBlankCsvRow(r));
                    this.previewRows = body.slice(0, this.maxPreviewRows);

                    if (chunkLimit < file.size) {
                        this.previewNotice = 'Preview shows the beginning of the file only.';
                    }
                })
                .catch(() => {
                    this.previewError = 'Could not read the file.';
                    input.value = '';
                });
        },

        clearFile() {
            const input = this.$refs.csvFile;
            if (input) {
                input.value = '';
            }
            this.previewHeaders = [];
            this.previewRows = [];
            this.previewError = '';
            this.previewNotice = '';
            this.fileLabel = '';
        },
    }));

    Alpine.data('dataSourceLibrary', (config) => ({
        tableDataBase: (config.tableDataBase ?? '').replace(/\/?$/, ''),
        blankGroupTabLabel:
            typeof config.blankGroupTabLabel === 'string' ? config.blankGroupTabLabel : '(blank)',
        uploadSummaries: Array.isArray(config.uploadSummaries) ? config.uploadSummaries : [],
        readOnlyById:
            config.readOnlyById &&
            typeof config.readOnlyById === 'object' &&
            !Array.isArray(config.readOnlyById)
                ? config.readOnlyById
                : {},
        activeId: config.initialActiveId ?? null,
        page: 1,
        headers: [],
        rows: [],
        rowOrdinals: [],
        columnOrder: [],
        heatRules: {},
        heatColumnStats: {},
        totalRows: 0,
        lastPage: 1,
        from: 0,
        to: 0,
        originalFilename: '',
        loading: false,
        loadError: '',
        playerNamesAll: [],
        playerNamesLoading: false,
        selectedPlayers: [],
        playerPickerQuery: '',
        playerPickerOpen: false,
        editingOrdinal: null,
        editPlayerDraft: '',
        columnDragFrom: null,
        columnDragOver: null,
        heatMenuForIdx: null,
        heatPaQualifier: { min: null, column_index: null },
        /** @type {boolean[]|null} Server: per displayed row, true if PA meets min (only when min PA is set). */
        heatRowPaOk: null,
        sortColumn: null,
        sortDirection: 'asc',
        thresholdDraft: [],
        heatMinPaDraft: '',
        groupByColumnRaw: '',
        groupValues: [],
        activeGroupValue: null,
        _groupColumnSelectSyncing: false,
        hsProfileFeedDraft: [],
        _pendingBrowseThresholds: null,
        _tableLoadSeq: 0,
        newRowCells: [],
        appendRowBusy: false,

        init() {
            queueMicrotask(async () => {
                if (!this.activeId) {
                    return;
                }
                this.applyBrowseSettingsFromSummary();
                await this.loadPage(this.page);
            });
        },

        /** Saved Min PA for the active dataset (from last persisted browse settings). */
        browseHeatMinPa() {
            const row = this.uploadSummaries.find((u) => Number(u.id) === Number(this.activeId));
            const hmpa = row?.dataset_browse_settings?.heat_min_pa;
            if (hmpa === undefined || hmpa === null || String(hmpa) === '') {
                return null;
            }
            const n = Number(hmpa);

            return !Number.isNaN(n) && n >= 0 ? n : null;
        },

        parsedGroupColumnIndex() {
            if (this.groupByColumnRaw === '' || this.groupByColumnRaw === null || this.groupByColumnRaw === undefined) {
                return null;
            }
            const n = parseInt(String(this.groupByColumnRaw), 10);

            return Number.isNaN(n) ? null : n;
        },

        syncGroupColumnSelectOptions() {
            const list = this.headers;
            const sel =
                document.getElementById('dataset_group_column') ?? this.$refs?.groupColumnSelect ?? null;
            if (!sel || !Array.isArray(list)) {
                return;
            }
            this._groupColumnSelectSyncing = true;
            try {
                const saved = String(this.groupByColumnRaw ?? '');
                while (sel.options.length > 1) {
                    sel.remove(1);
                }
                list.forEach((h, gIdx) => {
                    const o = document.createElement('option');
                    o.value = String(gIdx);
                    o.textContent = h !== '' ? String(h) : '—';
                    sel.appendChild(o);
                });
                if (saved !== '') {
                    const n = parseInt(saved, 10);
                    if (!Number.isNaN(n) && n >= 0 && n < list.length) {
                        this.groupByColumnRaw = String(n);
                        sel.value = String(n);
                    } else {
                        this.groupByColumnRaw = '';
                        this.activeGroupValue = null;
                        this.groupValues = [];
                        sel.value = '';
                    }
                } else {
                    sel.value = '';
                }
            } finally {
                queueMicrotask(() => {
                    this._groupColumnSelectSyncing = false;
                });
            }
        },

        get datasetGridStyle() {
            const n = Array.isArray(this.headers) ? this.headers.length : 0;
            if (n === 0) {
                return { gridTemplateColumns: 'minmax(10rem, 1fr)' };
            }
            if (n === 1) {
                return { gridTemplateColumns: 'minmax(10rem, 1fr)' };
            }

            return {
                gridTemplateColumns: `minmax(12rem, 1.15fr) repeat(${n - 1}, minmax(6.5rem, 1fr))`,
            };
        },

        get datasetGridMinWidth() {
            const n = Array.isArray(this.headers) ? this.headers.length : 0;
            if (n === 0) {
                return '12rem';
            }
            if (n === 1) {
                return '14rem';
            }

            return `${14 + (n - 1) * 6.5}rem`;
        },

        settingsUrl() {
            return `${this.tableDataBase}/${this.activeId}/settings`;
        },

        rowUrl(ordinal) {
            return `${this.tableDataBase}/${this.activeId}/rows/${ordinal}`;
        },

        get activeUploadReadOnly() {
            const id = this.activeId;
            if (id === null || id === undefined || id === '') {
                return false;
            }
            const key = String(id);
            const map = this.readOnlyById;
            if (map && typeof map === 'object' && Object.prototype.hasOwnProperty.call(map, key)) {
                return map[key] === true;
            }
            const row = this.uploadSummaries.find((u) => Number(u.id) === Number(id));
            if (!row) {
                return false;
            }

            return row.dataset_read_only === true || row.upload_kind === 'career_pg_master';
        },

        scrollToAppendRow() {
            document.getElementById('dataset-add-row')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },

        activeUploadName() {
            const id = this.activeId;
            const row = this.uploadSummaries.find((u) => Number(u.id) === Number(id));

            return row?.name ?? '';
        },

        syncHsProfileFeedDraft() {
            const row = this.uploadSummaries.find((u) => Number(u.id) === Number(this.activeId));
            if (!row) {
                this.hsProfileFeedDraft = [];

                return;
            }
            const slots = row.hs_profile_feed_slots;
            this.hsProfileFeedDraft = Array.isArray(slots) ? [...slots] : [];
        },

        applyBrowseSettingsFromSummary() {
            const row = this.uploadSummaries.find((u) => Number(u.id) === Number(this.activeId));
            const s = row?.dataset_browse_settings;
            if (!s || typeof s !== 'object') {
                this.selectedPlayers = [];
                this._pendingBrowseThresholds = null;
                this.groupByColumnRaw = '';
                this.activeGroupValue = null;
                this.heatMinPaDraft = '';

                return;
            }
            this.selectedPlayers = Array.isArray(s.players) ? s.players.map((p) => String(p)) : [];
            this._pendingBrowseThresholds = Array.isArray(s.column_thresholds) ? s.column_thresholds : null;
            const hmpa = s.heat_min_pa;
            this.heatMinPaDraft =
                hmpa !== undefined && hmpa !== null && String(hmpa) !== '' && !Number.isNaN(Number(hmpa))
                    ? String(hmpa)
                    : '';
            if (s.group_column !== undefined && s.group_column !== null && s.group_column !== '') {
                const gc = parseInt(String(s.group_column), 10);
                this.groupByColumnRaw = Number.isNaN(gc) ? '' : String(gc);
            } else {
                this.groupByColumnRaw = '';
            }
            if (Object.prototype.hasOwnProperty.call(s, 'group_value')) {
                const gv = s.group_value;
                this.activeGroupValue = gv === null || gv === undefined ? null : String(gv);
            } else {
                this.activeGroupValue = null;
            }
        },

        applyDatasetBrowseToSummary(data) {
            if (!Object.prototype.hasOwnProperty.call(data ?? {}, 'dataset_browse_settings')) {
                return;
            }
            const s = data.dataset_browse_settings;
            this.uploadSummaries = this.uploadSummaries.map((u) =>
                Number(u.id) === Number(this.activeId) ? { ...u, dataset_browse_settings: s } : u,
            );
        },

        applyHsProfileFeedAssignments(data) {
            if (!data?.hs_profile_feed_assignments || !Array.isArray(data.hs_profile_feed_assignments)) {
                return;
            }
            const slotMap = new Map(
                data.hs_profile_feed_assignments.map((s) => [
                    Number(s.id),
                    Array.isArray(s.hs_profile_feed_slots) ? s.hs_profile_feed_slots : [],
                ]),
            );
            this.uploadSummaries = this.uploadSummaries.map((u) => ({
                ...u,
                hs_profile_feed_slots: slotMap.has(Number(u.id)) ? slotMap.get(Number(u.id)) : [],
            }));
            this.syncHsProfileFeedDraft();
        },

        async saveDataset() {
            if (!this.activeId) {
                return;
            }
            try {
                const th = this.buildColumnThresholdsPayload();
                const gci = this.parsedGroupColumnIndex();
                const rawPa = String(this.heatMinPaDraft ?? '').trim();
                let heat_min_pa = null;
                if (rawPa !== '') {
                    const n = Number(rawPa);
                    if (!Number.isNaN(n) && n >= 0) {
                        heat_min_pa = n;
                    }
                }
                const browse = {
                    players: [...this.selectedPlayers],
                    column_thresholds: th,
                    group_column: gci,
                    group_value:
                        gci !== null && this.activeGroupValue !== null ? String(this.activeGroupValue) : null,
                    heat_min_pa,
                };
                const payload = { dataset_browse_settings: browse };
                if (!this.activeUploadReadOnly) {
                    payload.hs_profile_feed_slots = this.hsProfileFeedDraft;
                }
                const { data } = await window.axios.patch(this.settingsUrl(), payload);
                this.applyHsProfileFeedAssignments(data);
                this.applyDatasetBrowseToSummary(data);
                this.syncHsProfileFeedDraft();
                this.loadError = '';
                await this.loadPage(this.page);
            } catch {
                this.loadError = 'Could not save dataset settings.';
            }
        },

        /**
         * Recompute per-row heat eligibility from the current grid using Min PA draft (then saved qualifier).
         * Runs after every table load so cell colors cannot drift from visible rows/headers.
         */
        reconcileHeatRowPaOkFromGrid() {
            let min = null;
            const raw = String(this.heatMinPaDraft ?? '').trim();
            if (raw !== '') {
                const n = Number(raw);
                if (!Number.isNaN(n) && n >= 0) {
                    min = n;
                }
            }
            if (min === null) {
                const qm = this.heatPaQualifier?.min;
                if (qm !== undefined && qm !== null && String(qm) !== '') {
                    const n = Number(qm);
                    if (!Number.isNaN(n) && n >= 0) {
                        min = n;
                    }
                }
            }
            if (min === null) {
                min = this.browseHeatMinPa();
            }
            if (min === null) {
                if (
                    Array.isArray(this.heatRowPaOk) &&
                    this.rows.length > 0 &&
                    this.heatRowPaOk.length === this.rows.length
                ) {
                    return;
                }
                this.heatRowPaOk = null;

                return;
            }
            const col = this.plateAppearancesColumnIndex();
            if (!Array.isArray(this.rows)) {
                return;
            }
            if (col === null) {
                this.heatRowPaOk = this.rows.map(() => false);

                return;
            }
            this.heatRowPaOk = this.rows.map((row) => {
                const v = Number.parseFloat(String(row[col] ?? '').replace(/[,% ]/g, ''));
                if (Number.isNaN(v)) {
                    return false;
                }

                return v >= min;
            });
        },

        async applyHeatPaCutoff() {
            if (!this.activeId) {
                return;
            }
            const p = this.page && this.page > 0 ? this.page : 1;
            await this.loadPage(p);
        },

        toggleSortColumn(hIdx) {
            if (this.sortColumn === hIdx) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = hIdx;
                this.sortDirection = 'asc';
            }
            this.loadPage(1);
        },

        sortControlTitle(hIdx) {
            const label = String(this.headers[hIdx] ?? '').trim() || 'column';
            if (this.sortColumn !== hIdx) {
                return `Sort by ${label}`;
            }

            return this.sortDirection === 'asc'
                ? `Sorted by ${label}, ascending (click for descending)`
                : `Sorted by ${label}, descending (click for ascending)`;
        },

        syncThresholdDraftLength() {
            const n = Array.isArray(this.headers) ? this.headers.length : 0;
            const next = [];
            for (let i = 0; i < n; i++) {
                const prev = this.thresholdDraft[i];
                next[i] =
                    prev && typeof prev === 'object'
                        ? { min: prev.min ?? '', max: prev.max ?? '' }
                        : { min: '', max: '' };
            }
            this.thresholdDraft = next;
        },

        buildColumnThresholdsPayload() {
            const list = [];
            const n = Array.isArray(this.headers) ? this.headers.length : 0;
            for (let i = 0; i < n; i++) {
                const d = this.thresholdDraft[i];
                if (!d) {
                    continue;
                }
                const rawMin = String(d.min ?? '').trim();
                const rawMax = String(d.max ?? '').trim();
                const min = rawMin === '' ? NaN : Number(rawMin);
                const max = rawMax === '' ? NaN : Number(rawMax);
                /** @type {{ col: number, min?: number, max?: number }} */
                const o = { col: i };
                let ok = false;
                if (rawMin !== '' && !Number.isNaN(min)) {
                    o.min = min;
                    ok = true;
                }
                if (rawMax !== '' && !Number.isNaN(max)) {
                    o.max = max;
                    ok = true;
                }
                if (ok) {
                    list.push(o);
                }
            }

            return list;
        },

        onThresholdInputsChanged() {
            this.loadPage(1);
        },

        clearColumnThresholds() {
            this.syncThresholdDraftLength();
            for (let i = 0; i < this.thresholdDraft.length; i++) {
                this.thresholdDraft[i] = { min: '', max: '' };
            }
            this.loadPage(1);
        },

        async deleteActiveUpload() {
            if (this.activeId === null) {
                return;
            }
            const label = this.activeUploadName() || 'dataset';
            if (!window.confirm(`Delete “${label}” permanently? The saved CSV will be removed.`)) {
                return;
            }
            try {
                const { data } = await window.axios.delete(`${this.tableDataBase}/${this.activeId}`, {
                    headers: { Accept: 'application/json' },
                });
                const url = data?.redirect ?? '/data-sources';
                window.location.assign(url);
            } catch {
                this.loadError = 'Could not delete this dataset.';
            }
        },

        async init() {
            this.syncHsProfileFeedDraft();
            this.applyBrowseSettingsFromSummary();
            if (!this.activeId) {
                return;
            }
            await this.loadPlayerNames();
            await this.$nextTick();
            await this.loadPage(1);
        },

        get filteredPlayerPickerOptions() {
            const q = (this.playerPickerQuery ?? '').trim().toLowerCase();
            const selectedLower = new Set(this.selectedPlayers.map((s) => String(s).toLowerCase()));
            let list = this.playerNamesAll.filter((n) => !selectedLower.has(String(n).toLowerCase()));
            if (q !== '') {
                list = list.filter((n) => String(n).toLowerCase().includes(q));
            }

            return list.slice(0, 50);
        },

        async loadPlayerNames() {
            if (!this.activeId) {
                return;
            }
            this.playerNamesLoading = true;
            try {
                const { data } = await window.axios.get(`${this.tableDataBase}/${this.activeId}/player-names`, {
                    headers: { Accept: 'application/json' },
                });
                this.playerNamesAll = Array.isArray(data.names) ? data.names : [];
            } catch {
                this.playerNamesAll = [];
            } finally {
                this.playerNamesLoading = false;
            }
        },

        async selectPlayerFromPicker(name) {
            const s = String(name ?? '').trim();
            if (s === '') {
                return;
            }
            const exists = this.selectedPlayers.some((p) => String(p).toLowerCase() === s.toLowerCase());
            if (exists) {
                return;
            }
            this.selectedPlayers = [...this.selectedPlayers, s];
            this.playerPickerQuery = '';
            if (this.parsedGroupColumnIndex() !== null) {
                await this.fetchGroupValues();
                if (this.activeGroupValue !== null && !this.groupValues.includes(this.activeGroupValue)) {
                    this.activeGroupValue = null;
                }
            }
            await this.loadPage(1);
        },

        async removeSelectedPlayer(name) {
            this.selectedPlayers = this.selectedPlayers.filter((p) => p !== name);
            if (this.parsedGroupColumnIndex() !== null) {
                await this.fetchGroupValues();
                if (this.activeGroupValue !== null && !this.groupValues.includes(this.activeGroupValue)) {
                    this.activeGroupValue = null;
                }
            }
            await this.loadPage(1);
        },

        async selectUpload(id) {
            const n = Number(id);
            const changed = this.activeId !== n;
            this.activeId = n;
            this.syncHsProfileFeedDraft();
            if (changed) {
                this.headers = [];
                this.rows = [];
                this.rowOrdinals = [];
                this.columnOrder = [];
                this.heatRules = {};
                this.heatColumnStats = {};
                this.heatPaQualifier = { min: null, column_index: null };
                this.heatRowPaOk = null;
                this.playerPickerQuery = '';
                this.playerPickerOpen = false;
                this.sortColumn = null;
                this.sortDirection = 'asc';
                this.thresholdDraft = [];
                this.groupByColumnRaw = '';
                this.groupValues = [];
                this.activeGroupValue = null;
                this.page = 1;
                this.cancelEditPlayer();
                this.applyBrowseSettingsFromSummary();
                this.newRowCells = [];
                await this.loadPlayerNames();
            }
            await this.loadPage(this.page);
        },

        syncNewRowDraftLength() {
            const n = Array.isArray(this.headers) ? this.headers.length : 0;
            const next = [];
            for (let i = 0; i < n; i++) {
                next[i] = this.newRowCells[i] ?? '';
            }
            this.newRowCells = next;
        },

        buildFileOrderCellsFromDraft() {
            const n = this.headers.length;
            if (n === 0) {
                return [];
            }
            const order =
                Array.isArray(this.columnOrder) && this.columnOrder.length === n
                    ? this.columnOrder
                    : Array.from({ length: n }, (_, i) => i);
            const fileCells = new Array(n).fill('');
            for (let d = 0; d < n; d++) {
                const f = order[d];
                if (typeof f === 'number' && !Number.isNaN(f) && f >= 0 && f < n) {
                    fileCells[f] = String(this.newRowCells[d] ?? '');
                }
            }

            return fileCells;
        },

        async appendDatasetRow() {
            if (!this.activeId || this.headers.length === 0 || this.appendRowBusy) {
                return;
            }
            this.appendRowBusy = true;
            this.loadError = '';
            try {
                const cells = this.buildFileOrderCellsFromDraft();
                const { data } = await window.axios.post(
                    `${this.tableDataBase}/${this.activeId}/rows`,
                    { cells },
                    { headers: { Accept: 'application/json' } },
                );
                for (let i = 0; i < this.newRowCells.length; i++) {
                    this.newRowCells[i] = '';
                }
                const targetPage = typeof data.lastPage === 'number' ? data.lastPage : this.lastPage;
                await this.loadPage(targetPage);
            } catch (err) {
                const status = err?.response?.status;
                const body = err?.response?.data;
                const errs = body?.errors;
                if (status === 422 && errs && typeof errs === 'object') {
                    const flat = Object.values(errs).flat();
                    this.loadError = flat.length > 0 ? String(flat[0]) : body?.message || 'Could not add row.';
                } else {
                    this.loadError =
                        typeof body?.message === 'string' ? body.message : 'Could not add row.';
                }
            } finally {
                this.appendRowBusy = false;
            }
        },

        cancelEditPlayer() {
            this.editingOrdinal = null;
            this.editPlayerDraft = '';
        },

        startEditPlayer(ordinal, name) {
            this.editingOrdinal = ordinal;
            this.editPlayerDraft = name ?? '';
        },

        async saveEditPlayer() {
            if (this.editingOrdinal === null) {
                return;
            }
            try {
                await window.axios.patch(this.rowUrl(this.editingOrdinal), {
                    player: this.editPlayerDraft,
                });
                this.cancelEditPlayer();
                await this.loadPage(this.page);
            } catch {
                this.loadError = 'Could not save player name.';
            }
        },

        async removePlayer(ordinal) {
            if (!window.confirm('Remove this player row from the saved CSV file?')) {
                return;
            }
            try {
                const { data } = await window.axios.delete(this.rowUrl(ordinal));
                if (typeof data.row_count === 'number') {
                    this.totalRows = data.row_count;
                }
                this.cancelEditPlayer();
                await this.loadPage(this.page);
            } catch {
                this.loadError = 'Could not delete row.';
            }
        },

        heatRuleTitle(headerName) {
            const r = this.heatRules[headerName];
            if (!r || !r.enabled) {
                return 'Column colors: off — click to change';
            }
            const stats = this.heatColumnStats[headerName];
            const min = Number(stats?.min);
            const max = Number(stats?.max);
            if (!stats || Number.isNaN(min) || Number.isNaN(max) || Math.abs(max - min) < 1e-6) {
                return 'Column colors: on (no range — all values match or non-numeric) — click to change';
            }

            return r.higher_is_better
                ? 'Column colors: red = high — click to change'
                : 'Column colors: red = low — click to change';
        },

        heatIsOn(headerName) {
            const r = this.heatRules[headerName];
            if (!r?.enabled) {
                return false;
            }
            const stats = this.heatColumnStats[headerName];
            const min = Number(stats?.min);
            const max = Number(stats?.max);

            return !(!stats || Number.isNaN(min) || Number.isNaN(max) || Math.abs(max - min) < 1e-6);
        },

        heatButtonSurface(headerName) {
            const r = this.heatRules[headerName];
            if (!r?.enabled) {
                return { backgroundColor: '#f3f4f6' };
            }
            const stats = this.heatColumnStats[headerName];
            const min = Number(stats?.min);
            const max = Number(stats?.max);
            if (!stats || Number.isNaN(min) || Number.isNaN(max) || Math.abs(max - min) < 1e-6) {
                return { backgroundColor: '#f3f4f6' };
            }

            return r.higher_is_better
                ? { background: 'linear-gradient(90deg, #fecaca 0%, #bfdbfe 100%)' }
                : { background: 'linear-gradient(90deg, #bfdbfe 0%, #fecaca 100%)' };
        },

        toggleHeatMenu(hIdx) {
            this.heatMenuForIdx = this.heatMenuForIdx === hIdx ? null : hIdx;
        },

        closeHeatMenu() {
            this.heatMenuForIdx = null;
        },

        async pickHeatRule(headerName, mode) {
            this.heatMenuForIdx = null;
            await this.setHeatRule(headerName, mode);
        },

        async setHeatRule(headerName, mode) {
            let next;
            if (mode === 'off') {
                next = { enabled: false, higher_is_better: true };
            } else if (mode === 'high') {
                next = { enabled: true, higher_is_better: true };
            } else {
                next = { enabled: true, higher_is_better: false };
            }
            this.heatRules = { ...this.heatRules, [headerName]: next };
            await this.persistHeatRules();
        },

        async persistHeatRules() {
            try {
                await window.axios.patch(this.settingsUrl(), { heat_rules: this.heatRules });
                await this.loadPage(this.page);
            } catch {
                this.loadError = 'Could not save heat rules.';
            }
        },

        onColumnDragStart(hIdx, event) {
            if (hIdx <= 0) {
                event.preventDefault();

                return;
            }
            this.columnDragFrom = hIdx;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(hIdx));
        },

        onColumnDragOver(hIdx, event) {
            if (hIdx <= 0 || this.columnDragFrom === null) {
                return;
            }
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            this.columnDragOver = hIdx;
        },

        onColumnDragLeave(hIdx) {
            if (this.columnDragOver === hIdx) {
                this.columnDragOver = null;
            }
        },

        onColumnDragEnd() {
            this.columnDragFrom = null;
            this.columnDragOver = null;
        },

        async onColumnDrop(hIdx, event) {
            event.preventDefault();
            const raw = event.dataTransfer.getData('text/plain');
            const parsed = Number.parseInt(raw, 10);
            const fromIdx = Number.isFinite(parsed) ? parsed : this.columnDragFrom;
            this.columnDragOver = null;
            this.columnDragFrom = null;
            if (fromIdx === null || fromIdx <= 0 || hIdx <= 0) {
                return;
            }
            await this.moveColumnTo(fromIdx, hIdx);
        },

        async moveColumnTo(fromIdx, toIdx) {
            const n = this.headers.length;
            if (fromIdx === toIdx || fromIdx <= 0 || toIdx <= 0 || fromIdx >= n || toIdx >= n) {
                return;
            }
            let order = [...this.columnOrder];
            if (order.length !== n) {
                order = Array.from({ length: n }, (_, i) => i);
            }
            const [removed] = order.splice(fromIdx, 1);
            order.splice(toIdx, 0, removed);
            try {
                await window.axios.patch(this.settingsUrl(), { column_order: order });
                await this.loadPage(this.page);
                if (this.parsedGroupColumnIndex() !== null) {
                    await this.fetchGroupValues();
                    if (this.activeGroupValue !== null && !this.groupValues.includes(this.activeGroupValue)) {
                        this.activeGroupValue = null;
                        await this.loadPage(this.page);
                    }
                }
            } catch {
                this.loadError = 'Could not reorder columns.';
            }
        },

        rowOrdinalAt(rIdx) {
            const o = this.rowOrdinals[rIdx];

            return o === undefined || o === null ? null : o;
        },

        /**
         * Threshold for PA gating: API (`heat_pa_qualifier`) plus the input draft so coloring works even if the response omits `min`.
         */
        get resolvedHeatMinPa() {
            const q = this.heatPaQualifier;
            if (q?.min !== undefined && q?.min !== null && String(q.min) !== '') {
                const n = Number(q.min);
                if (!Number.isNaN(n) && n >= 0) {
                    return n;
                }
            }
            const raw = String(this.heatMinPaDraft ?? '').trim();
            if (raw !== '') {
                const n = Number(raw);
                if (!Number.isNaN(n) && n >= 0) {
                    return n;
                }
            }
            const saved = this.browseHeatMinPa();
            if (saved !== null) {
                return saved;
            }

            return null;
        },

        /**
         * Same idea as {@see App\Support\DataSourceCsvHeaders::plateAppearancesColumnIndex} — must match row indices in `this.headers`.
         */
        plateAppearancesColumnIndex() {
            const list = this.headers;
            if (!Array.isArray(list)) {
                return null;
            }
            for (let i = 0; i < list.length; i++) {
                let norm = String(list[i] ?? '')
                    .replace(/^\ufeff/, '')
                    .replace(/[\u00a0\u2007\u202f\u3000]/g, ' ')
                    .trim()
                    .toLowerCase();
                norm = norm.replace(/\s+/g, ' ').trim();
                const slug = norm.replace(/%/g, 'pct').replace(/[^a-z0-9]+/gi, '');
                if (
                    norm === 'pa' ||
                    norm === 'pas' ||
                    norm === 'plate appearances' ||
                    norm === 'plate appearance' ||
                    norm.includes('plate appearance') ||
                    slug === 'pa' ||
                    slug === 'pas'
                ) {
                    return i;
                }
                const tokens = norm.split(/[^a-z0-9%]+/i).filter(Boolean);
                for (const tok of tokens) {
                    const t = tok.replace(/%/g, 'pct').toLowerCase();
                    if (t === 'pa' || t === 'pas') {
                        return i;
                    }
                }
            }
            for (let j = 0; j < list.length; j++) {
                const letters = String(list[j] ?? '')
                    .replace(/[^a-z]/gi, '')
                    .toLowerCase();
                if (letters === 'pa' || letters === 'pas') {
                    return j;
                }
            }

            return null;
        },

        rowMeetsHeatPaQualifier(row) {
            const min = this.resolvedHeatMinPa;
            if (min === null) {
                return true;
            }
            const colIdx = this.plateAppearancesColumnIndex();
            if (colIdx === undefined || colIdx === null) {
                return false;
            }
            const paRaw = row[colIdx];
            const pa = Number.parseFloat(String(paRaw ?? '').replace(/[,% ]/g, ''));
            if (Number.isNaN(pa)) {
                return false;
            }

            return pa >= min;
        },

        datasetCellStyle(headerName, raw, row, rIdx) {
            if (
                Array.isArray(this.heatRowPaOk) &&
                rIdx !== undefined &&
                rIdx !== null &&
                Number.isFinite(Number(rIdx)) &&
                Number(rIdx) >= 0 &&
                Number(rIdx) < this.heatRowPaOk.length &&
                this.heatRowPaOk[Number(rIdx)] === false
            ) {
                return null;
            }
            void this.resolvedHeatMinPa;
            const rule = this.heatRules[headerName];
            const stats = this.heatColumnStats[headerName];
            if (!rule?.enabled || !stats || stats.min === undefined || stats.max === undefined) {
                return null;
            }
            if (
                this.heatRowPaOk === null &&
                row !== undefined &&
                row !== null &&
                !this.rowMeetsHeatPaQualifier(row)
            ) {
                return null;
            }
            const v = Number.parseFloat(String(raw).replace(/[,% ]/g, ''));
            if (Number.isNaN(v)) {
                return null;
            }
            const min = Number(stats.min);
            const max = Number(stats.max);
            const medianFallback = (min + max) / 2;
            const median =
                stats.median !== undefined && stats.median !== null ? Number(stats.median) : medianFallback;
            if (Number.isNaN(min) || Number.isNaN(max) || Number.isNaN(median) || Math.abs(max - min) < 1e-6) {
                return null;
            }
            const eps = 1e-6;
            /** t in [0,1]: 0 = red, 0.5 = white (median), 1 = blue */
            let t;
            if (rule.higher_is_better) {
                if (v <= median) {
                    t = median - min < eps ? 0.5 : 0.5 + (0.5 * (median - v)) / (median - min);
                } else {
                    t = max - median < eps ? 0.5 : 0.5 - (0.5 * (v - median)) / (max - median);
                }
            } else if (v <= median) {
                t = median - min < eps ? 0.5 : (0.5 * (v - min)) / (median - min);
            } else {
                t = max - median < eps ? 0.5 : 0.5 + (0.5 * (v - median)) / (max - median);
            }
            t = Math.min(1, Math.max(0, t));
            const redR = 255;
            const redG = 0;
            const redB = 0;
            const blueR = 90;
            const blueG = 125;
            const blueB = 188;
            let r;
            let g;
            let b;
            if (t <= 0.5) {
                const linearU = t / 0.5;
                const u = linearU ** 1.12;
                r = Math.round(redR + (255 - redR) * u);
                g = Math.round(redG + (255 - redG) * u);
                b = Math.round(redB + (255 - redB) * u);
            } else {
                const linearU = (t - 0.5) / 0.5;
                const u = 1 - (1 - linearU) ** 2;
                r = Math.round(255 + (blueR - 255) * u);
                g = Math.round(255 + (blueG - 255) * u);
                b = Math.round(255 + (blueB - 255) * u);
            }
            const whiteText = t <= 0.15 || t >= 0.85;

            return {
                backgroundColor: `rgb(${r},${g},${b})`,
                color: whiteText ? '#ffffff' : '#111827',
            };
        },

        async loadPage(p) {
            if (!this.activeId) {
                return;
            }
            this._tableLoadSeq = (this._tableLoadSeq ?? 0) + 1;
            const loadSeq = this._tableLoadSeq;
            const uploadIdForRequest = this.activeId;
            this.loading = true;
            this.loadError = '';
            this.page = p;
            try {
                const params = { page: p };
                if (this.selectedPlayers.length > 0) {
                    params.players = this.selectedPlayers;
                }
                if (this.sortColumn !== null && typeof this.sortColumn === 'number') {
                    params.sort_column = this.sortColumn;
                    params.sort_direction = this.sortDirection;
                }
                let thList;
                if (Array.isArray(this.headers) && this.headers.length > 0) {
                    thList = this.buildColumnThresholdsPayload();
                } else if (this._pendingBrowseThresholds !== null && Array.isArray(this._pendingBrowseThresholds)) {
                    thList = this._pendingBrowseThresholds;
                } else {
                    thList = [];
                }
                if (thList.length > 0) {
                    params.column_thresholds = JSON.stringify(thList);
                }
                const groupCol = this.parsedGroupColumnIndex();
                if (groupCol !== null && this.activeGroupValue !== null) {
                    params.group_column = groupCol;
                    params.group_value =
                        this.activeGroupValue === '' ? '__EMPTY__' : String(this.activeGroupValue);
                }
                const paDraft = String(this.heatMinPaDraft ?? '').trim();
                let paParam = null;
                if (paDraft !== '') {
                    const paN = Number(paDraft);
                    if (!Number.isNaN(paN) && paN >= 0) {
                        paParam = paN;
                    }
                } else {
                    paParam = this.browseHeatMinPa();
                }
                if (paParam !== null) {
                    params.heat_min_pa = paParam;
                }
                const { data } = await window.axios.get(`${this.tableDataBase}/${this.activeId}/table-data`, {
                    params,
                    headers: { Accept: 'application/json' },
                });
                if (loadSeq !== this._tableLoadSeq || this.activeId !== uploadIdForRequest) {
                    return;
                }
                this.headers = data.headers ?? [];
                this.syncThresholdDraftLength();
                this.rows = data.rows ?? [];
                this.rowOrdinals = data.row_ordinals ?? [];
                this.columnOrder = Array.isArray(data.column_order) ? data.column_order : [];
                this.heatRules =
                    data.heat_rules && typeof data.heat_rules === 'object' && !Array.isArray(data.heat_rules)
                        ? { ...data.heat_rules }
                        : {};
                this.heatColumnStats =
                    data.heat_column_stats &&
                    typeof data.heat_column_stats === 'object' &&
                    !Array.isArray(data.heat_column_stats)
                        ? { ...data.heat_column_stats }
                        : {};
                const hpq = data.heat_pa_qualifier;
                if (hpq && typeof hpq === 'object' && !Array.isArray(hpq)) {
                    const c = hpq.column_index;
                    this.heatPaQualifier = {
                        min: hpq.min !== undefined && hpq.min !== null ? Number(hpq.min) : null,
                        column_index:
                            c !== undefined && c !== null && String(c) !== '' && !Number.isNaN(Number(c))
                                ? Number(c)
                                : null,
                    };
                } else {
                    this.heatPaQualifier = { min: null, column_index: null };
                }
                const hrpo = data.heat_row_pa_ok;
                if (Array.isArray(hrpo) && hrpo.length > 0) {
                    this.heatRowPaOk = hrpo.map((v) => v === true || v === 1 || v === '1');
                } else {
                    this.heatRowPaOk = null;
                }
                this.reconcileHeatRowPaOkFromGrid();
                this.page = data.page ?? 1;
                this.lastPage = data.lastPage ?? 1;
                this.from = data.from ?? 0;
                this.to = data.to ?? 0;
                this.totalRows = data.totalRows ?? 0;
                this.originalFilename = data.original_filename ?? '';
                this.heatMenuForIdx = null;
                if (data.sort && typeof data.sort.column === 'number') {
                    this.sortColumn = data.sort.column;
                    this.sortDirection = data.sort.direction === 'desc' ? 'desc' : 'asc';
                } else {
                    this.sortColumn = null;
                }
                if (this._pendingBrowseThresholds !== null && Array.isArray(this._pendingBrowseThresholds)) {
                    const list = this._pendingBrowseThresholds;
                    this._pendingBrowseThresholds = null;
                    for (const item of list) {
                        if (!item || typeof item !== 'object') {
                            continue;
                        }
                        const col = Number.parseInt(String(item.col), 10);
                        if (Number.isNaN(col) || col < 0 || col >= this.thresholdDraft.length) {
                            continue;
                        }
                        const minV = item.min;
                        const maxV = item.max;
                        const min =
                            minV !== undefined &&
                            minV !== null &&
                            String(minV) !== '' &&
                            !Number.isNaN(Number(minV))
                                ? String(minV)
                                : '';
                        const max =
                            maxV !== undefined &&
                            maxV !== null &&
                            String(maxV) !== '' &&
                            !Number.isNaN(Number(maxV))
                                ? String(maxV)
                                : '';
                        this.thresholdDraft[col] = { min, max };
                    }
                }
                this.syncNewRowDraftLength();
            } catch (err) {
                if (loadSeq !== this._tableLoadSeq || this.activeId !== uploadIdForRequest) {
                    return;
                }
                const status = err?.response?.status;
                this.loadError =
                    status === 404
                        ? 'That dataset file is missing on the server.'
                        : 'Could not load this dataset. Check the connection and try again.';
                this.headers = [];
                this.thresholdDraft = [];
                this.rows = [];
                this.rowOrdinals = [];
                this.columnOrder = [];
                this.heatRules = {};
                this.heatColumnStats = {};
                this.heatPaQualifier = { min: null, column_index: null };
                this.heatRowPaOk = null;
                this.heatMenuForIdx = null;
                this.sortColumn = null;
            } finally {
                if (loadSeq === this._tableLoadSeq) {
                    this.loading = false;
                }
            }
            if (loadSeq !== this._tableLoadSeq || this.activeId !== uploadIdForRequest) {
                return;
            }
            await this.$nextTick();
            this.syncGroupColumnSelectOptions();
            if (this.parsedGroupColumnIndex() !== null) {
                await this.fetchGroupValues();
            }
        },

        async onGroupByColumnChanged(ev) {
            if (this._groupColumnSelectSyncing) {
                return;
            }
            const raw = ev?.target?.value;
            const fromSelect = raw === undefined || raw === null ? null : String(raw);
            if (fromSelect !== null && fromSelect === this.groupByColumnRaw) {
                return;
            }
            if (fromSelect !== null) {
                this.groupByColumnRaw = fromSelect;
            }
            await this.$nextTick();
            this.activeGroupValue = null;
            this.groupValues = [];
            if (this.parsedGroupColumnIndex() === null) {
                await this.loadPage(1);

                return;
            }
            await this.fetchGroupValues();
            await this.loadPage(1);
        },

        async fetchGroupValues() {
            const gci = this.parsedGroupColumnIndex();
            if (!this.activeId || gci === null) {
                this.groupValues = [];

                return;
            }
            try {
                const params = { group_column: gci };
                if (this.selectedPlayers.length > 0) {
                    params.players = this.selectedPlayers;
                }
                const { data } = await window.axios.get(`${this.tableDataBase}/${this.activeId}/group-values`, {
                    params,
                    headers: { Accept: 'application/json' },
                });
                this.groupValues = Array.isArray(data.values)
                    ? data.values.map((v) => (v === null || v === undefined ? '' : String(v)))
                    : [];
            } catch {
                this.groupValues = [];
            }
        },

        selectGroupTab(value) {
            this.activeGroupValue = value === null || value === undefined ? null : String(value);
            this.loadPage(1);
        },
    }));

    Alpine.data('playerListTable', (config) => ({
        rows: config.rows,
        deleteConfirm: config.deleteConfirm ?? '',
        filterQuery: '',
        sortKey: 'rk',
        sortDir: 'asc',

        sortField(key) {
            const map = {
                rk: 'aggregate_rank',
                player: 'name',
                pool: 'player_pool',
                school: 'school',
                pos: 'position',
                agg: 'aggregate_score',
                mdl: 'mdl',
                mlb: 'mlb',
                espn: 'espn',
                law: 'law',
                fg: 'fg',
                ba: 'ba',
                profile: 'profile_url',
            };

            return map[key];
        },

        isNumericSortKey(key) {
            return ['rk', 'agg', 'mdl', 'mlb', 'espn', 'law', 'fg', 'ba'].includes(key);
        },

        compareNum(a, b, asc) {
            const na = a === null || a === undefined || Number.isNaN(a);
            const nb = b === null || b === undefined || Number.isNaN(b);
            if (na && nb) {
                return 0;
            }
            if (na) {
                return 1;
            }
            if (nb) {
                return -1;
            }
            const cmp = asc ? a - b : b - a;

            return cmp;
        },

        compareStr(a, b, asc) {
            const sa = (a ?? '').toString().toLowerCase();
            const sb = (b ?? '').toString().toLowerCase();
            const cmp = sa.localeCompare(sb, undefined, { sensitivity: 'base' });

            return asc ? cmp : -cmp;
        },

        compareProfile(a, b, asc) {
            const ha = a ? 1 : 0;
            const hb = b ? 1 : 0;
            if (ha !== hb) {
                return asc ? ha - hb : hb - ha;
            }

            return 0;
        },

        get filteredRows() {
            const q = this.filterQuery.trim().toLowerCase();
            if (q === '') {
                return [...this.rows];
            }

            return this.rows.filter((r) => {
                const school = (r.school ?? '').toLowerCase();
                const pos = (r.position ?? '').toLowerCase();

                return (
                    r.name.toLowerCase().includes(q) ||
                    school.includes(q) ||
                    r.player_pool.toLowerCase().includes(q) ||
                    pos.includes(q)
                );
            });
        },

        get displayRows() {
            const rows = [...this.filteredRows];
            const key = this.sortKey;
            const asc = this.sortDir === 'asc';
            const field = this.sortField(key);

            rows.sort((a, b) => {
                if (key === 'profile') {
                    return this.compareProfile(a.profile_url, b.profile_url, asc);
                }
                const va = a[field];
                const vb = b[field];
                if (this.isNumericSortKey(key)) {
                    return this.compareNum(va, vb, asc);
                }

                return this.compareStr(va, vb, asc);
            });

            return rows;
        },

        get heatStats() {
            const fields = [
                ['rk', 'aggregate_rank'],
                ['agg', 'aggregate_score'],
                ['mdl', 'mdl'],
                ['mlb', 'mlb'],
                ['espn', 'espn'],
                ['law', 'law'],
                ['fg', 'fg'],
                ['ba', 'ba'],
            ];
            const out = {};
            // Use the full loaded list for min/max so colors stay meaningful when the table is filtered.
            for (const [heatKey, rowKey] of fields) {
                const vals = this.rows
                    .map((r) => r[rowKey])
                    .filter((v) => v !== null && v !== undefined && !Number.isNaN(Number(v)));
                if (vals.length === 0) {
                    out[heatKey] = { empty: true, min: 0, max: 0 };
                } else {
                    const nums = vals.map((v) => Number(v));
                    out[heatKey] = {
                        empty: false,
                        min: Math.min(...nums),
                        max: Math.max(...nums),
                    };
                }
            }

            return out;
        },

        cellHeatStyle(heatKey, value) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return null;
            }
            const s = this.heatStats[heatKey];
            if (!s || s.empty || s.min === s.max) {
                return null;
            }
            const n = Number(value);
            const t = (n - s.min) / (s.max - s.min);
            // Very bright red (best / low) → white (mid) → very dark blue (worst / high); solid fills.
            const redR = 255;
            const redG = 0;
            const redB = 0;
            const blueR = 90;
            const blueG = 125;
            const blueB = 188;
            let r;
            let g;
            let bch;
            if (t <= 0.5) {
                const linearU = t / 0.5;
                const u = linearU ** 1.12;
                r = Math.round(redR + (255 - redR) * u);
                g = Math.round(redG + (255 - redG) * u);
                bch = Math.round(redB + (255 - redB) * u);
            } else {
                const linearU = (t - 0.5) / 0.5;
                const u = 1 - (1 - linearU) ** 2;
                r = Math.round(255 + (blueR - 255) * u);
                g = Math.round(255 + (blueG - 255) * u);
                bch = Math.round(255 + (blueB - 255) * u);
            }

            const whiteText = t <= 0.2 || t >= 0.8;

            return {
                backgroundColor: `rgb(${r},${g},${bch})`,
                color: whiteText ? '#ffffff' : '#111827',
            };
        },

        sortBy(key) {
            if (this.sortKey === key) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortDir = 'asc';
            }
        },

        sortHighlightHeader(key) {
            return this.sortKey === key ? 'bg-yellow-100/50' : '';
        },

        sortHighlightBody(key) {
            return this.sortKey === key ? 'bg-yellow-50' : '';
        },

        formatRank(v) {
            return v !== null && v !== undefined ? String(v) : '—';
        },

        formatAgg(v) {
            return v !== null && v !== undefined ? Number(v).toFixed(1) : '—';
        },

        confirmDelete(event) {
            if (this.deleteConfirm !== '' && !window.confirm(this.deleteConfirm)) {
                event.preventDefault();
            }
        },
    }));

    Alpine.data('ncaaPlayerCombobox', (config) => ({
        players: config.players,
        selectedId: config.selectedId,
        selectedLabel: config.selectedLabel,
        open: false,
        query: '',

        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (q === '') {
                return this.players;
            }

            return this.players.filter((p) => p.label.toLowerCase().includes(q));
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.query = '';
                this.$nextTick(() => {
                    this.$refs.filterInput?.focus({ preventScroll: true });
                });
            }
        },

        close() {
            this.open = false;
            this.query = '';
        },

        choose(p) {
            if (p.url) {
                window.location.href = p.url;
            }
        },
    }));

    /**
     * Notes page player picker: fixed panel + capped height so the name list scrolls inside
     * the dropdown and does not scroll the document (or grow the page).
     */
    Alpine.data('notesPlayerCombobox', (config) => ({
        players: Array.isArray(config.players) ? config.players : [],
        selectedId: config.selectedId,
        selectedLabel: config.selectedLabel,
        placeholderSelect: config.placeholderSelect ?? '',
        placeholderFilter: config.placeholderFilter ?? '',
        open: false,
        query: '',

        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (q === '') {
                return this.players;
            }

            return this.players.filter((p) => (p.label ?? '').toLowerCase().includes(q));
        },

        onComboboxFocus() {
            if (!this.open) {
                this.open = true;
                this.query = '';
            }
        },

        onComboboxInput(event) {
            if (!this.open) {
                this.open = true;
            }
            this.query = event.target.value;
        },

        close() {
            this.open = false;
            this.query = '';
        },

        choose(p) {
            if (p.url) {
                window.location.href = p.url;
            }
        },
    }));
});

Alpine.start();
