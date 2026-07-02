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

function readWorkingBoardConfig() {
    const el = document.getElementById('working-boards-config');
    if (!el?.textContent) {
        return {};
    }
    try {
        return JSON.parse(el.textContent);
    } catch {
        return {};
    }
}

function readPlayerListConfig() {
    const el = document.getElementById('player-list-config');
    if (!el?.textContent) {
        return {};
    }
    try {
        return JSON.parse(el.textContent);
    } catch {
        return {};
    }
}

/** Mirrors App\Support\NoteGradeInputAppearance::summaryCellStyle (profile grade grid). */
const GRADE_STYLE = {
    NAVY: [12, 35, 64],
    THREE: [106, 130, 193],
    FIVE: [250, 218, 221],
    SIX: [242, 128, 128],
    RED: [233, 52, 35],
};

function gradeIsExactlyFive(value) {
    return Math.abs(value - 5.0) < 1e-6;
}

function gradeRgbForValue(value, min, max) {
    const mid = (min + max) / 2.0;

    if (max <= min) {
        return GRADE_STYLE.NAVY;
    }

    if (value > mid) {
        if (min <= 6 && max >= 6 && mid < 6) {
            if (value <= 6) {
                const den = Math.max(1, 6 - mid);
                const u = (value - mid) / den;

                return [
                    Math.round(GRADE_STYLE.FIVE[0] + (GRADE_STYLE.SIX[0] - GRADE_STYLE.FIVE[0]) * u),
                    Math.round(GRADE_STYLE.FIVE[1] + (GRADE_STYLE.SIX[1] - GRADE_STYLE.FIVE[1]) * u),
                    Math.round(GRADE_STYLE.FIVE[2] + (GRADE_STYLE.SIX[2] - GRADE_STYLE.FIVE[2]) * u),
                ];
            }

            const den = Math.max(1, max - 6);
            const u = (value - 6) / den;

            return [
                Math.round(GRADE_STYLE.SIX[0] + (GRADE_STYLE.RED[0] - GRADE_STYLE.SIX[0]) * u),
                Math.round(GRADE_STYLE.SIX[1] + (GRADE_STYLE.RED[1] - GRADE_STYLE.SIX[1]) * u),
                Math.round(GRADE_STYLE.SIX[2] + (GRADE_STYLE.RED[2] - GRADE_STYLE.SIX[2]) * u),
            ];
        }

        const den = Math.max(1, max - mid);
        const u = (value - mid) / den;

        return [
            Math.round(GRADE_STYLE.FIVE[0] + (GRADE_STYLE.RED[0] - GRADE_STYLE.FIVE[0]) * u),
            Math.round(GRADE_STYLE.FIVE[1] + (GRADE_STYLE.RED[1] - GRADE_STYLE.FIVE[1]) * u),
            Math.round(GRADE_STYLE.FIVE[2] + (GRADE_STYLE.RED[2] - GRADE_STYLE.FIVE[2]) * u),
        ];
    }

    if (min <= 3 && mid >= 3) {
        if (value <= 3) {
            const den = Math.max(1, 3 - min);
            const u = (value - min) / den;

            return [
                Math.round(GRADE_STYLE.NAVY[0] + (GRADE_STYLE.THREE[0] - GRADE_STYLE.NAVY[0]) * u),
                Math.round(GRADE_STYLE.NAVY[1] + (GRADE_STYLE.THREE[1] - GRADE_STYLE.NAVY[1]) * u),
                Math.round(GRADE_STYLE.NAVY[2] + (GRADE_STYLE.THREE[2] - GRADE_STYLE.NAVY[2]) * u),
            ];
        }

        const den = Math.max(1, mid - 3);
        const u = (value - 3) / den;

        return [
            Math.round(GRADE_STYLE.THREE[0] + (GRADE_STYLE.FIVE[0] - GRADE_STYLE.THREE[0]) * u),
            Math.round(GRADE_STYLE.THREE[1] + (GRADE_STYLE.FIVE[1] - GRADE_STYLE.THREE[1]) * u),
            Math.round(GRADE_STYLE.THREE[2] + (GRADE_STYLE.FIVE[2] - GRADE_STYLE.THREE[2]) * u),
        ];
    }

    const den = Math.max(1, mid - min);
    const u = (value - min) / den;

    return [
        Math.round(GRADE_STYLE.NAVY[0] + (GRADE_STYLE.FIVE[0] - GRADE_STYLE.NAVY[0]) * u),
        Math.round(GRADE_STYLE.NAVY[1] + (GRADE_STYLE.FIVE[1] - GRADE_STYLE.NAVY[1]) * u),
        Math.round(GRADE_STYLE.NAVY[2] + (GRADE_STYLE.FIVE[2] - GRADE_STYLE.NAVY[2]) * u),
    ];
}

/** Conf / risk 1–5: red (1) → green (5). Risk H=1 … L=5 uses the same scale. */
const BOARD_SCALE_COLORS = {
    1: '#ec7c77',
    2: '#f7cac9',
    3: '#FEE69C',
    4: '#b8d68c',
    5: '#7dbd7d',
};

function boardScaleFillStyle(value) {
    if (value === '' || value === null || value === undefined) {
        return 'background-color:#ffffff;';
    }
    const n = Number(value);
    if (Number.isNaN(n)) {
        return 'background-color:#ffffff;';
    }
    const clamped = Math.max(1, Math.min(5, Math.round(n)));
    const color = BOARD_SCALE_COLORS[clamped] ?? '#ffffff';

    return `background-color:${color};`;
}

function gradeSummaryStyle(value, min = 2, max = 7) {
    if (value === null || value === undefined || value === '') {
        return 'background-color: #ffffff; color: #0f172a; font-weight: 700;';
    }

    const n = Number(value);
    if (Number.isNaN(n)) {
        return 'background-color: #ffffff; color: #0f172a; font-weight: 700;';
    }

    const clamped = Math.max(min, Math.min(max, n));
    const [r, g, b] = gradeRgbForValue(clamped, min, max);
    const textColor = gradeIsExactlyFive(clamped) ? '#0f172a' : '#ffffff';

    return `background-color: rgb(${r},${g},${b}); color: ${textColor}; font-weight: 700;`;
}

/** Bat column: #F9696A (high) ↔ #FFFFFF (median) ↔ #5A8AC6 (low), app-wide bounds. */
const BAT_GRADE_HEX_LOW = '#5A8AC6';
const BAT_GRADE_HEX_MID = '#FFFFFF';
const BAT_GRADE_HEX_HIGH = '#F9696A';

/** Mirrors App\Support\GradeScaleAppearance anchor palette (Role / Swing columns). */
const GRADE_SCALE_ANCHORS = [
    { grade: 3.0, hex: '#5A8AC6' },
    { grade: 4.0, hex: '#ACC3E2' },
    { grade: 4.5, hex: '#D3E0F0' },
    { grade: 5.0, hex: '#FFFFFF' },
    { grade: 5.5, hex: '#FBD8DB' },
    { grade: 6.0, hex: '#FAB3B5' },
    { grade: 7.0, hex: '#F9696A' },
];

/** @deprecated Board role/swing previously used per-board percentile colors. */
const BAT_COLOR_RED = [229, 115, 115];
const BAT_COLOR_WHITE = [255, 255, 255];
const BAT_COLOR_BLUE = [96, 130, 182];

function lerpRgb(a, b, t) {
    const u = Math.max(0, Math.min(1, t));

    return [
        Math.round(a[0] + (b[0] - a[0]) * u),
        Math.round(a[1] + (b[1] - a[1]) * u),
        Math.round(a[2] + (b[2] - a[2]) * u),
    ];
}

function numericGrade(v) {
    if (v === null || v === undefined || v === '') {
        return null;
    }
    const n = Number(v);

    return Number.isNaN(n) ? null : n;
}

/** HS: perf, k-zone, adj, damage, swing. NCAA: perf, k-zone, damage, adj, platoon, swing. */
function batGradeForCard(card) {
    const pool = card?.player_pool === 'ncaa' ? 'ncaa' : 'hs';
    const fields =
        pool === 'ncaa'
            ? [
                  card?.grade_perf,
                  card?.grade_approach,
                  card?.grade_damage,
                  card?.grade_adj,
                  card?.grade_contact,
                  card?.grade_swing,
              ]
            : [
                  card?.grade_perf,
                  card?.grade_approach,
                  card?.grade_contact,
                  card?.grade_damage,
                  card?.grade_swing,
              ];
    const nums = fields.map((f) => numericGrade(f));
    if (nums.some((n) => n === null)) {
        return null;
    }

    return nums.reduce((sum, n) => sum + n, 0) / nums.length;
}

function isTierDivider(item) {
    return item?.entry_type === 'tier_divider';
}

function isPassRoundKey(roundKey) {
    return String(roundKey ?? '').endsWith('-pass');
}

function isNonTargetDivider(item) {
    return item?.entry_type === 'non_target_divider';
}

function isRoundDivider(item) {
    return isTierDivider(item) || isNonTargetDivider(item);
}

function nonTargetDividerIndex(list) {
    if (!Array.isArray(list)) {
        return -1;
    }
    for (let i = 0; i < list.length; i++) {
        if (isNonTargetDivider(list[i])) {
            return i;
        }
    }

    return -1;
}

function dedupeNonTargetDividers(list) {
    if (!Array.isArray(list)) {
        return [];
    }

    return list.filter((item) => !isNonTargetDivider(item));
}

function ensureNonTargetDividerOnList(list) {
    return dedupeNonTargetDividers(Array.isArray(list) ? list : []);
}

function insertIndexBeforeNonTargetDivider(list) {
    return Array.isArray(list) ? list.length : 0;
}

function collectBatGradesOnBoard(boardRounds, boardType, roundKeys) {
    const values = [];
    for (const rk of roundKeys) {
        const cards = boardRounds?.[boardType]?.[rk] ?? [];
        for (const card of cards) {
            if (isRoundDivider(card)) {
                continue;
            }
            const bat = batGradeForCard(card);
            if (bat !== null) {
                values.push(bat);
            }
        }
    }

    return values;
}

function collectGradeFieldOnBoard(boardRounds, boardType, roundKeys, field) {
    const values = [];
    for (const rk of roundKeys) {
        const cards = boardRounds?.[boardType]?.[rk] ?? [];
        for (const card of cards) {
            if (isRoundDivider(card)) {
                continue;
            }
            const n = numericGrade(card?.[field]);
            if (n !== null) {
                values.push(n);
            }
        }
    }

    return values;
}

function percentileBoundsFromValues(values) {
    if (values.length === 0) {
        return { min: null, max: null, median: null };
    }
    const sorted = [...values].sort((a, b) => a - b);
    const min = sorted[0];
    const max = sorted[sorted.length - 1];
    const mid = Math.floor(sorted.length / 2);
    const median =
        sorted.length % 2 === 1 ? sorted[mid] : (sorted[mid - 1] + sorted[mid]) / 2;

    return { min, max, median };
}

function batPercentileBounds(boardRounds, boardType, roundKeys) {
    return percentileBoundsFromValues(collectBatGradesOnBoard(boardRounds, boardType, roundKeys));
}

function gradeFieldPercentileBounds(boardRounds, boardType, roundKeys, field) {
    return percentileBoundsFromValues(collectGradeFieldOnBoard(boardRounds, boardType, roundKeys, field));
}

function relativeLuminance(rgb) {
    const linear = rgb.map((channel) => {
        const c = channel / 255;

        return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * linear[0] + 0.7152 * linear[1] + 0.0722 * linear[2];
}

function textColorForRgb(rgb) {
    return relativeLuminance(rgb) > 0.5 ? '#000000' : '#ffffff';
}

function parseHexColor(hex) {
    const normalized = hex.replace('#', '');

    return [
        parseInt(normalized.slice(0, 2), 16),
        parseInt(normalized.slice(2, 4), 16),
        parseInt(normalized.slice(4, 6), 16),
    ];
}

function lerpHexColor(hexA, hexB, t) {
    const rgb = lerpRgb(parseHexColor(hexA), parseHexColor(hexB), t);

    return (
        '#' +
        rgb
            .map((channel) => Math.round(channel).toString(16).padStart(2, '0'))
            .join('')
            .toUpperCase()
    );
}

function gradeScaleHexForValue(value) {
    if (value === null || value === undefined) {
        return null;
    }

    const grade = Math.max(3.0, Math.min(7.0, value));
    const stops = GRADE_SCALE_ANCHORS;

    if (grade <= stops[0].grade) {
        return stops[0].hex;
    }

    const last = stops[stops.length - 1];
    if (grade >= last.grade) {
        return last.hex;
    }

    for (let i = 0; i < stops.length - 1; i++) {
        const low = stops[i];
        const high = stops[i + 1];
        if (grade < low.grade || grade > high.grade) {
            continue;
        }

        if (Math.abs(grade - low.grade) < 1e-6) {
            return low.hex;
        }

        if (Math.abs(grade - high.grade) < 1e-6) {
            return high.hex;
        }

        const u = (grade - low.grade) / (high.grade - low.grade);

        return lerpHexColor(low.hex, high.hex, u);
    }

    return last.hex;
}

function gradeScaleBoardCellStyle(value, fontWeight = 700) {
    const n = numericGrade(value);
    if (n === null) {
        return `background-color:#ffffff;color:#000000;font-weight:${fontWeight};`;
    }

    const hex = gradeScaleHexForValue(n);
    const textColor = textColorForRgb(parseHexColor(hex));

    return `background-color:${hex};color:${textColor};font-weight:${fontWeight};`;
}

function boardPercentileCellStyle(value, bounds) {
    if (value === null || value === undefined) {
        return 'background-color:#ffffff;color:#000000;font-weight:700;';
    }

    const { min, max, median } = bounds;
    if (min === null || max === null || median === null) {
        return 'background-color:#ffffff;color:#000000;font-weight:700;';
    }

    if (max === min) {
        return 'background-color:#ffffff;color:#000000;font-weight:700;';
    }

    let rgb;
    if (value >= median) {
        const den = Math.max(1e-9, max - median);
        const t = (value - median) / den;
        rgb = lerpRgb(BAT_COLOR_WHITE, BAT_COLOR_RED, t);
    } else {
        const den = Math.max(1e-9, median - min);
        const t = (value - min) / den;
        rgb = lerpRgb(BAT_COLOR_BLUE, BAT_COLOR_WHITE, t);
    }

    return `background-color:rgb(${rgb[0]},${rgb[1]},${rgb[2]});color:#000000;font-weight:700;`;
}

function batGradePercentileCellStyle(value, bounds, fontWeight = 700) {
    if (value === null || value === undefined) {
        return `background-color:#ffffff;color:#000000;font-weight:${fontWeight};`;
    }

    const { min, max, median } = bounds;
    if (min === null || max === null || median === null) {
        return `background-color:#ffffff;color:#000000;font-weight:${fontWeight};`;
    }

    if (max === min) {
        return `background-color:#ffffff;color:#000000;font-weight:${fontWeight};`;
    }

    let hex;
    if (value >= median) {
        const den = Math.max(1e-9, max - median);
        const t = (value - median) / den;
        hex = lerpHexColor(BAT_GRADE_HEX_MID, BAT_GRADE_HEX_HIGH, t);
    } else {
        const den = Math.max(1e-9, median - min);
        const t = (value - min) / den;
        hex = lerpHexColor(BAT_GRADE_HEX_LOW, BAT_GRADE_HEX_MID, t);
    }

    const textColor = textColorForRgb(parseHexColor(hex));

    return `background-color:${hex};color:${textColor};font-weight:${fontWeight};`;
}

/** @deprecated Use {@link batGradePercentileCellStyle}. */
function batCellStyle(value, bounds) {
    return batGradePercentileCellStyle(value, bounds);
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

    Alpine.data('dataSourceLibrary', (config) => {
        const normalizedTableBase = (config.tableDataBase ?? '').replace(/\/?$/, '');
        /*
         * NCAA portal URLs contain "data-sources" as a substring ("ncaa-data-sources"), so never infer
         * HS vs NCAA from that alone. If Blade @js() ever omits profile-feed keys, wrong defaults would
         * PATCH hs_profile_feed_slots with NCAA slot keys → 422 and nothing persists.
         */
        const isNcaaPortal = normalizedTableBase.includes('ncaa-data-sources');
        const profileFeedDefaults = isNcaaPortal
            ? {
                  summary: 'ncaa_profile_feed_slots',
                  payload: 'ncaa_profile_feed_slots',
                  assignmentsResp: 'ncaa_profile_feed_assignments',
                  assignmentsEach: 'ncaa_profile_feed_slots',
              }
            : {
                  summary: 'hs_profile_feed_slots',
                  payload: 'hs_profile_feed_slots',
                  assignmentsResp: 'hs_profile_feed_assignments',
                  assignmentsEach: 'hs_profile_feed_slots',
              };

        return {
        tableDataBase: normalizedTableBase,
        libraryIndexPath: (config.libraryIndexPath ?? '/data-sources').replace(/\/?$/, ''),
        profileFeedSlotsSummaryField:
            config.profileFeedSlotsSummaryField ?? profileFeedDefaults.summary,
        profileFeedSlotsPayloadKey: config.profileFeedSlotsPayloadKey ?? profileFeedDefaults.payload,
        profileFeedAssignmentsResponseKey:
            config.profileFeedAssignmentsResponseKey ?? profileFeedDefaults.assignmentsResp,
        profileFeedAssignmentsEachSlotField:
            config.profileFeedAssignmentsEachSlotField ?? profileFeedDefaults.assignmentsEach,
        blankGroupTabLabel:
            typeof config.blankGroupTabLabel === 'string' ? config.blankGroupTabLabel : '(blank)',
        uploadSummaries: Array.isArray(config.uploadSummaries) ? config.uploadSummaries : [],
        readOnlyById:
            config.readOnlyById &&
            typeof config.readOnlyById === 'object' &&
            !Array.isArray(config.readOnlyById)
                ? config.readOnlyById
                : {},
        hardContactVisualsTabId:
            typeof config.hardContactVisualsTabId === 'string' && config.hardContactVisualsTabId !== ''
                ? config.hardContactVisualsTabId
                : null,
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
        /** `P` (pitch count) or `PA` (plate appearances) for heat volume gate. */
        heatVolumeHeaderDraft: 'P',
        groupByColumnRaw: '',
        groupValues: [],
        activeGroupValue: null,
        _groupColumnSelectSyncing: false,
        hsProfileFeedDraft: [],
        pitchTypeFeedDraft: '',
        _pendingBrowseThresholds: null,
        _tableLoadSeq: 0,
        newRowCells: [],
        appendRowBusy: false,
        renameDraft: '',
        renameBusy: false,
        renameError: '',

        get isHardContactVisualsActive() {
            return (
                this.hardContactVisualsTabId !== null &&
                this.activeId === this.hardContactVisualsTabId
            );
        },

        get isDatasetTabActive() {
            return this.activeId !== null && !this.isHardContactVisualsActive;
        },

        selectHardContactVisuals() {
            const tabId = this.hardContactVisualsTabId;
            if (!tabId) {
                return;
            }
            const changed = this.activeId !== tabId;
            this.activeId = tabId;
            if (changed) {
                this.loading = false;
                this.loadError = '';
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
                this.newRowCells = [];
            }
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

        syncRenameDraft() {
            this.renameDraft = String(this.activeUploadName() ?? '');
            this.renameError = '';
        },

        heatVolumeKindColumnPresent(kind) {
            const k = String(kind ?? '').trim().toUpperCase();
            if (k === 'P') {
                return this.pitchCountColumnIndex() !== null;
            }
            if (k === 'PA') {
                return this.plateAppearancesColumnIndex() !== null;
            }

            return false;
        },

        defaultHeatVolumeKind() {
            if (this.pitchCountColumnIndex() !== null) {
                return 'P';
            }
            if (this.plateAppearancesColumnIndex() !== null) {
                return 'PA';
            }

            return 'P';
        },

        ensureHeatVolumeDraftValid() {
            if (!Array.isArray(this.headers) || this.headers.length === 0) {
                return;
            }
            const row = this.uploadSummaries.find((u) => Number(u.id) === Number(this.activeId));
            const savedHvh = row?.dataset_browse_settings?.heat_volume_header;
            if (savedHvh !== undefined && savedHvh !== null && String(savedHvh).trim() !== '') {
                const t = String(savedHvh).trim();
                const u = t.toUpperCase();
                if (u !== 'P' && u !== 'PA') {
                    const idx = this.columnIndexForHeaderName(this.headers, t);
                    const pIdx = this.pitchCountColumnIndex();
                    const paIdx = this.plateAppearancesColumnIndex();
                    if (idx !== null && pIdx === idx) {
                        this.heatVolumeHeaderDraft = 'P';
                    } else if (idx !== null && paIdx === idx) {
                        this.heatVolumeHeaderDraft = 'PA';
                    }
                }
            }
            const hasP = this.pitchCountColumnIndex() !== null;
            const hasPa = this.plateAppearancesColumnIndex() !== null;
            let k = String(this.heatVolumeHeaderDraft ?? '').trim().toUpperCase();
            if (k !== 'P' && k !== 'PA') {
                k = this.defaultHeatVolumeKind();
            }
            if (k === 'P' && !hasP) {
                k = hasPa ? 'PA' : 'P';
            }
            if (k === 'PA' && !hasPa) {
                k = hasP ? 'P' : 'PA';
            }
            this.heatVolumeHeaderDraft = k;
        },

        columnIndexForHeaderName(list, name) {
            const want = String(name ?? '').trim();
            if (want === '') {
                return null;
            }
            if (!Array.isArray(list)) {
                return null;
            }
            for (let i = 0; i < list.length; i++) {
                if (String(list[i] ?? '').trim() === want) {
                    return i;
                }
            }
            const lw = want.toLowerCase();
            for (let j = 0; j < list.length; j++) {
                if (String(list[j] ?? '').trim().toLowerCase() === lw) {
                    return j;
                }
            }

            return null;
        },

        headerSlugForHeatVolume(header) {
            let t = String(header ?? '')
                .replace(/^\ufeff/, '')
                .replace(/[\u00a0\u2007\u202f\u3000]/g, ' ')
                .trim()
                .toLowerCase();
            t = t.replace(/\s+/g, ' ').trim();
            t = t.replace(/%/g, 'pct');

            return t.replace(/[^a-z0-9]+/gi, '');
        },

        pitchCountColumnIndex() {
            const list = this.headers;
            if (!Array.isArray(list)) {
                return null;
            }
            for (let i = 0; i < list.length; i++) {
                if (this.headerSlugForHeatVolume(list[i]) === 'p') {
                    return i;
                }
            }
            for (let j = 0; j < list.length; j++) {
                const slug = this.headerSlugForHeatVolume(list[j]);
                if (
                    slug === 'pitches' ||
                    slug === 'pitchcount' ||
                    slug === 'pitchcounts'
                ) {
                    return j;
                }
                const raw = String(list[j] ?? '');
                const norm = raw
                    .replace(/^\ufeff/, '')
                    .replace(/[\u00a0\u2007\u202f\u3000]/g, ' ')
                    .trim()
                    .toLowerCase()
                    .replace(/\s+/g, ' ')
                    .trim();
                if (norm === 'pitch count' || norm === 'pitch counts') {
                    return j;
                }
            }

            return null;
        },

        heatVolumeGateColumnIndex() {
            if (!Array.isArray(this.headers)) {
                return null;
            }
            const k = String(this.heatVolumeHeaderDraft ?? '').trim().toUpperCase();
            if (k === 'P') {
                return this.pitchCountColumnIndex();
            }
            if (k === 'PA') {
                return this.plateAppearancesColumnIndex();
            }

            return null;
        },

        syncHsProfileFeedDraft() {
            const row = this.uploadSummaries.find((u) => Number(u.id) === Number(this.activeId));
            if (!row) {
                this.hsProfileFeedDraft = [];
                this.pitchTypeFeedDraft = '';

                return;
            }
            const field = this.profileFeedSlotsSummaryField;
            const slots = row[field];
            this.hsProfileFeedDraft = Array.isArray(slots) ? [...slots] : [];
            this.pitchTypeFeedDraft =
                typeof row.pitch_type_feed === 'string' && row.pitch_type_feed !== ''
                    ? row.pitch_type_feed
                    : '';
        },

        profileFeedSlotChecked(slotKey) {
            const k = String(slotKey ?? '');

            return Array.isArray(this.hsProfileFeedDraft) && this.hsProfileFeedDraft.includes(k);
        },

        toggleProfileFeedSlot(slotKey, checked) {
            const k = String(slotKey ?? '');
            if (k === '') {
                return;
            }
            const cur = Array.isArray(this.hsProfileFeedDraft) ? [...this.hsProfileFeedDraft] : [];
            const set = new Set(cur);
            if (checked) {
                set.add(k);
            } else {
                set.delete(k);
            }
            this.hsProfileFeedDraft = Array.from(set);
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
                this.heatVolumeHeaderDraft = 'P';

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
            const hvh = s.heat_volume_header;
            if (hvh !== undefined && hvh !== null && String(hvh).trim() !== '') {
                const t = String(hvh).trim();
                const u = t.toUpperCase();
                this.heatVolumeHeaderDraft = u === 'P' || u === 'PA' ? u : t;
            } else {
                this.heatVolumeHeaderDraft = 'P';
            }
            if (Array.isArray(this.headers) && this.headers.length > 0) {
                this.ensureHeatVolumeDraftValid();
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

        applyPitchTypeFeedToSummary(data) {
            if (!Object.prototype.hasOwnProperty.call(data ?? {}, 'pitch_type_feed')) {
                return;
            }
            const feed = data.pitch_type_feed;
            this.uploadSummaries = this.uploadSummaries.map((u) =>
                Number(u.id) === Number(this.activeId)
                    ? {
                          ...u,
                          pitch_type_feed:
                              typeof feed === 'string' && feed !== '' ? feed : null,
                      }
                    : u,
            );
        },

        applyHsProfileFeedAssignments(data) {
            const respKey = this.profileFeedAssignmentsResponseKey;
            const slotKey = this.profileFeedAssignmentsEachSlotField;
            const list = data?.[respKey];
            if (!list || !Array.isArray(list)) {
                return;
            }
            const slotMap = new Map(
                list.map((s) => [Number(s.id), Array.isArray(s[slotKey]) ? s[slotKey] : []]),
            );
            const summaryField = this.profileFeedSlotsSummaryField;
            this.uploadSummaries = this.uploadSummaries.map((u) => {
                const id = Number(u.id);
                if (!slotMap.has(id)) {
                    return u;
                }

                return {
                    ...u,
                    [summaryField]: slotMap.get(id) ?? [],
                };
            });
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
                const hvDraft = String(this.heatVolumeHeaderDraft ?? '').trim().toUpperCase();
                let heat_volume_header = null;
                if (hvDraft === 'P' && this.pitchCountColumnIndex() !== null) {
                    heat_volume_header = 'P';
                } else if (hvDraft === 'PA' && this.plateAppearancesColumnIndex() !== null) {
                    heat_volume_header = 'PA';
                }
                const browse = {
                    players: [...this.selectedPlayers],
                    column_thresholds: th,
                    group_column: gci,
                    group_value:
                        gci !== null && this.activeGroupValue !== null ? String(this.activeGroupValue) : null,
                    heat_min_pa,
                    heat_volume_header,
                };
                const payload = { dataset_browse_settings: browse };
                /*
                 * Always send slot assignments: they are upload metadata (which profile tables this CSV
                 * feeds), not row edits. Read-only datasets still need slots saved; the server ignores slot
                 * payloads for HS Career PG master rows only.
                 */
                payload[this.profileFeedSlotsPayloadKey] = Array.isArray(this.hsProfileFeedDraft)
                    ? [...this.hsProfileFeedDraft]
                    : [];
                const rawPitchFeed = String(this.pitchTypeFeedDraft ?? '').trim().toUpperCase();
                payload.pitch_type_feed =
                    rawPitchFeed === 'FB' || rawPitchFeed === 'BB' || rawPitchFeed === 'OS' ? rawPitchFeed : null;
                const { data } = await window.axios.patch(this.settingsUrl(), payload, {
                    headers: { Accept: 'application/json' },
                });
                this.applyHsProfileFeedAssignments(data);
                this.applyDatasetBrowseToSummary(data);
                this.applyPitchTypeFeedToSummary(data);
                this.syncHsProfileFeedDraft();
                this.loadError = '';
                await this.loadPage(this.page);
            } catch {
                this.loadError = 'Could not save dataset settings.';
            }
        },

        async saveDatasetName() {
            if (!this.activeId || this.activeUploadReadOnly || this.renameBusy) {
                return;
            }
            const name = String(this.renameDraft ?? '').trim();
            if (name === '') {
                this.renameError = 'Display name cannot be empty.';

                return;
            }
            this.renameBusy = true;
            this.renameError = '';
            try {
                const { data } = await window.axios.patch(
                    this.settingsUrl(),
                    { name },
                    { headers: { Accept: 'application/json' } },
                );
                const id = Number(this.activeId);
                const finalName = typeof data?.name === 'string' ? data.name : name;
                this.uploadSummaries = this.uploadSummaries.map((u) =>
                    Number(u.id) === id ? { ...u, name: finalName } : u,
                );
                this.syncRenameDraft();
            } catch (err) {
                const status = err?.response?.status;
                const body = err?.response?.data;
                const errs = body?.errors;
                if (status === 422 && errs?.name?.[0]) {
                    this.renameError = String(errs.name[0]);
                } else {
                    this.renameError =
                        typeof body?.message === 'string' ? body.message : 'Could not save display name.';
                }
            } finally {
                this.renameBusy = false;
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
            const qc = this.heatPaQualifier?.column_index;
            let col =
                qc !== undefined && qc !== null && String(qc) !== '' && !Number.isNaN(Number(qc))
                    ? Number(qc)
                    : null;
            if (col === null) {
                col = this.heatVolumeGateColumnIndex();
            }
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
                const url = data?.redirect ?? this.libraryIndexPath;
                window.location.assign(url);
            } catch {
                this.loadError = 'Could not delete this dataset.';
            }
        },

        async init() {
            this.syncRenameDraft();
            this.$watch('activeId', () => {
                this.syncRenameDraft();
            });
            if (this.isHardContactVisualsActive) {
                return;
            }
            this.syncHsProfileFeedDraft();
            this.applyBrowseSettingsFromSummary();
            if (!this.activeId) {
                return;
            }
            await this.loadPlayerNames();
            await this.$nextTick();
            await this.loadPage(1);
            this.syncHsProfileFeedDraft();
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
            if (this.hardContactVisualsTabId && id === this.hardContactVisualsTabId) {
                this.selectHardContactVisuals();

                return;
            }
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
            const qc = this.heatPaQualifier?.column_index;
            let colIdx =
                qc !== undefined && qc !== null && String(qc) !== '' && !Number.isNaN(Number(qc))
                    ? Number(qc)
                    : null;
            if (colIdx === null) {
                colIdx = this.heatVolumeGateColumnIndex();
            }
            if (colIdx === undefined || colIdx === null) {
                return false;
            }
            const raw = row[colIdx];
            const v = Number.parseFloat(String(raw ?? '').replace(/[,% ]/g, ''));
            if (Number.isNaN(v)) {
                return false;
            }

            return v >= min;
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
            if (!this.activeId || this.isHardContactVisualsActive) {
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
                const hvDraft = String(this.heatVolumeHeaderDraft ?? '').trim().toUpperCase();
                if (hvDraft === 'P' || hvDraft === 'PA') {
                    params.heat_volume_header = hvDraft;
                } else {
                    params.heat_volume_header = '__auto__';
                }
                const { data } = await window.axios.get(`${this.tableDataBase}/${this.activeId}/table-data`, {
                    params,
                    headers: { Accept: 'application/json' },
                });
                if (loadSeq !== this._tableLoadSeq || this.activeId !== uploadIdForRequest) {
                    return;
                }
                this.headers = data.headers ?? [];
                this.ensureHeatVolumeDraftValid();
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
        };
    });

    Alpine.data('playerListTable', () => {
        const config = readPlayerListConfig();

        return {
        rows: Array.isArray(config.rows) ? config.rows : [],
        readOnly: Boolean(config.readOnly),
        deleteConfirm: config.deleteConfirm ?? '',
        playersPatchBase: String(config.playersPatchBase ?? '/players').replace(/\/$/, ''),
        gradeMin: Number(config.gradeMin ?? 2),
        gradeMax: Number(config.gradeMax ?? 7),
        boardScaleMin: Number(config.boardScaleMin ?? 1),
        boardScaleMax: Number(config.boardScaleMax ?? 5),
        filterQuery: '',
        poolFilter: 'all',
        advancedOpen: false,
        thresholdKeys: ['role', 'conf', 'risk', 'bat', 'perf', 'k_zone', 'damage', 'adj', 'platoon', 'swing'],
        thresholdFilters: {
            role: '',
            conf: '',
            risk: '',
            bat: '',
            perf: '',
            k_zone: '',
            damage: '',
            adj: '',
            platoon: '',
            swing: '',
        },
        thresholdLabels: {
            role: 'Role',
            conf: 'Conf',
            risk: 'Risk',
            bat: 'Bat',
            perf: 'Perf',
            k_zone: 'K-Zone',
            damage: 'Damage',
            adj: 'Adj',
            platoon: 'L/R',
            swing: 'Swing',
        },
        sortOptions: [
            { key: 'player', label: 'Player' },
            { key: 'pool', label: 'Pool' },
            { key: 'school', label: 'School' },
            { key: 'role', label: 'Role' },
            { key: 'conf', label: 'Conf' },
            { key: 'risk', label: 'Risk' },
            { key: 'bat', label: 'Bat' },
            { key: 'perf', label: 'Perf' },
            { key: 'k_zone', label: 'K-Zone' },
            { key: 'damage', label: 'Damage' },
            { key: 'adj', label: 'Adj' },
            { key: 'platoon', label: 'L/R' },
            { key: 'swing', label: 'Swing' },
        ],
        sortKey: 'player',
        sortDir: 'asc',
        sortKey2: '',
        sortDir2: 'asc',
        editingId: null,
        editDraft: {},
        editFieldErrors: {},
        saving: false,

        init() {
            this.$watch('sortKey', (value) => {
                if (value && value === this.sortKey2) {
                    this.sortKey2 = '';
                }
            });
            this.$watch('sortKey2', (value) => {
                if (value && value === this.sortKey) {
                    this.sortKey2 = '';
                }
            });
        },

        thresholdBounds(key) {
            if (key === 'conf' || key === 'risk') {
                return { min: this.boardScaleMin, max: this.boardScaleMax, step: 1 };
            }

            return { min: this.gradeMin, max: this.gradeMax, step: 0.5 };
        },

        hasActiveThresholds() {
            return this.poolFilter !== 'all'
                || this.thresholdKeys.some((key) => String(this.thresholdFilters[key] ?? '').trim() !== '');
        },

        clearAdvancedFilters() {
            this.poolFilter = 'all';
            this.thresholdKeys.forEach((key) => {
                this.thresholdFilters[key] = '';
            });
        },

        passesThresholds(row) {
            for (const key of this.thresholdKeys) {
                const raw = String(this.thresholdFilters[key] ?? '').trim();
                if (raw === '') {
                    continue;
                }
                const min = Number(raw);
                if (Number.isNaN(min)) {
                    continue;
                }
                const val = row[key];
                if (val === null || val === undefined || Number(val) < min) {
                    return false;
                }
            }

            return true;
        },

        startEdit(row) {
            if (this.readOnly) {
                return;
            }
            this.editFieldErrors = {};
            this.editingId = row.id;
            this.editDraft = {
                first_name: row.first_name,
                last_name: row.last_name,
                player_pool: row.player_pool_key ?? row.player_pool?.toLowerCase?.() ?? 'ncaa',
                school: row.school ?? '',
            };
        },

        firstError(field) {
            const e = this.editFieldErrors[field];

            return Array.isArray(e) && e.length ? e[0] : '';
        },

        cancelEdit() {
            this.editingId = null;
            this.editDraft = {};
            this.editFieldErrors = {};
        },

        async saveEdit(rowId) {
            if (this.readOnly || this.saving) {
                return;
            }
            this.saving = true;
            this.editFieldErrors = {};
            const d = this.editDraft;
            const payload = {
                first_name: d.first_name,
                last_name: d.last_name,
                player_pool: d.player_pool,
                school: d.school === '' ? null : d.school,
            };
            try {
                const { data } = await window.axios.patch(`${this.playersPatchBase}/${rowId}`, payload, {
                    headers: { Accept: 'application/json' },
                });
                const idx = this.rows.findIndex((r) => r.id === rowId);
                if (idx !== -1 && data.row) {
                    this.rows[idx] = data.row;
                }
                this.cancelEdit();
            } catch (e) {
                if (e.response?.status === 422 && e.response.data?.errors) {
                    this.editFieldErrors = e.response.data.errors;
                } else {
                    window.alert('Could not save changes.');
                }
            } finally {
                this.saving = false;
            }
        },

        sortField(key) {
            const map = {
                player: 'name',
                pool: 'player_pool',
                school: 'school',
                role: 'role',
                conf: 'conf',
                risk: 'risk',
                bat: 'bat',
                perf: 'perf',
                k_zone: 'k_zone',
                damage: 'damage',
                adj: 'adj',
                platoon: 'platoon',
                swing: 'swing',
                profile: 'profile_url',
            };

            return map[key];
        },

        isNumericSortKey(key) {
            return ['role', 'conf', 'risk', 'bat', 'perf', 'k_zone', 'damage', 'adj', 'platoon', 'swing'].includes(key);
        },

        activeSortLevels() {
            const levels = [{ key: this.sortKey, dir: this.sortDir }];
            if (this.sortKey2 && this.sortKey2 !== this.sortKey) {
                levels.push({ key: this.sortKey2, dir: this.sortDir2 });
            }

            return levels;
        },

        compareBySortKey(a, b, key, asc) {
            if (key === 'profile') {
                return this.compareProfile(a.profile_url, b.profile_url, asc);
            }
            const field = this.sortField(key);
            const va = a[field];
            const vb = b[field];
            if (this.isNumericSortKey(key)) {
                return this.compareNum(va, vb, asc);
            }

            return this.compareStr(va, vb, asc);
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

            return this.rows.filter((r) => {
                if (this.poolFilter !== 'all' && r.player_pool.toLowerCase() !== this.poolFilter) {
                    return false;
                }
                if (!this.passesThresholds(r)) {
                    return false;
                }
                if (q === '') {
                    return true;
                }
                const school = (r.school ?? '').toLowerCase();

                return (
                    r.name.toLowerCase().includes(q) ||
                    school.includes(q) ||
                    r.player_pool.toLowerCase().includes(q)
                );
            });
        },

        get displayRows() {
            const rows = [...this.filteredRows];
            const levels = this.activeSortLevels();

            rows.sort((a, b) => {
                for (const level of levels) {
                    const asc = level.dir === 'asc';
                    const cmp = this.compareBySortKey(a, b, level.key, asc);
                    if (cmp !== 0) {
                        return cmp;
                    }
                }

                return this.compareStr(a.name, b.name, true);
            });

            return rows;
        },

        sortTier(key) {
            if (this.sortKey === key) {
                return 1;
            }
            if (this.sortKey2 && this.sortKey2 === key) {
                return 2;
            }

            return 0;
        },

        ariaSort(key) {
            const tier = this.sortTier(key);
            if (tier === 0) {
                return 'none';
            }

            return (tier === 1 ? this.sortDir : this.sortDir2) === 'asc' ? 'ascending' : 'descending';
        },

        sortBy(key) {
            if (this.sortKey === key) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortDir = 'asc';
            }
        },

        sortIndicator(key) {
            const tier = this.sortTier(key);
            if (tier === 0) {
                return '';
            }
            const asc = tier === 1 ? this.sortDir === 'asc' : this.sortDir2 === 'asc';
            const arrow = asc ? '▲' : '▼';

            return tier === 2 ? `²${arrow}` : arrow;
        },

        sortHighlightHeader(key) {
            const tier = this.sortTier(key);
            if (tier === 1) {
                return 'player-list-sort-active';
            }
            if (tier === 2) {
                return 'player-list-sort-secondary';
            }

            return '';
        },

        sortHighlightBody(key) {
            return this.sortTier(key) > 0 ? 'bg-yellow-50' : '';
        },

        confirmDelete(event) {
            if (this.readOnly) {
                event.preventDefault();

                return;
            }
            if (this.deleteConfirm !== '' && !window.confirm(this.deleteConfirm)) {
                event.preventDefault();
            }
        },
        };
    });

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

    Alpine.store('workingBoardBridge', {
        addHandler: null,
        boardRef: null,
        register(handler) {
            this.addHandler = handler;
        },
        setBoard(board) {
            this.boardRef = board;
        },
        isPlayerOnBoard(boardType, playerId) {
            if (typeof this.boardRef?.isPlayerOnBoard === 'function') {
                return this.boardRef.isPlayerOnBoard(boardType, playerId);
            }

            return false;
        },
        add(detail) {
            if (typeof this.addHandler === 'function') {
                this.addHandler(detail);
            }
        },
    });

    Alpine.data('boardPlayerPicker', (config) => ({
        boardType: config.boardType ?? 'hs',
        players: Array.isArray(config.players) ? config.players : [],
        roundKeys: Array.isArray(config.roundKeys) ? config.roundKeys : ['1-targets'],
        round: Array.isArray(config.roundKeys) && config.roundKeys.length > 0 ? config.roundKeys[0] : '1-targets',
        readOnly: Boolean(config.readOnly),
        open: false,
        query: '',
        selectedPlayerIds: [],

        completeAvailableCount() {
            return this.players.filter(
                (p) => p.profile_complete && !this.isPlayerOnBoard(p.player_id),
            ).length;
        },

        availableCount() {
            return this.players.filter((p) => !this.isPlayerOnBoard(p.player_id)).length;
        },

        availablePlayers() {
            return this.players.filter((p) => !this.isPlayerOnBoard(p.player_id));
        },

        get filtered() {
            const q = this.query.trim().toLowerCase();
            let list = this.availablePlayers();
            if (q !== '') {
                const tokens = q.split(/[\s,]+/).filter((t) => t.length > 0);
                list = list.filter((p) => {
                    const hay = String(p.search_blob ?? p.label ?? '').toLowerCase();

                    return tokens.every((token) => hay.includes(token));
                });
            }

            const complete = list.filter((p) => p.profile_complete);
            const incomplete = list.filter((p) => !p.profile_complete);

            return [...complete, ...incomplete];
        },

        selectedCount() {
            return this.selectedPlayerIds.length;
        },

        isPlayerOnBoard(playerId) {
            if (typeof this.$parent?.isPlayerOnBoard === 'function') {
                return this.$parent.isPlayerOnBoard(this.boardType, playerId);
            }

            return Alpine.store('workingBoardBridge').isPlayerOnBoard(this.boardType, playerId);
        },

        isPlayerSelected(playerId) {
            return this.selectedPlayerIds.includes(Number(playerId));
        },

        togglePlayer(player) {
            if (this.readOnly || this.isPlayerOnBoard(player?.player_id)) {
                return;
            }
            const id = Number(player.player_id);
            if (!id || Number.isNaN(id)) {
                return;
            }
            if (this.isPlayerSelected(id)) {
                this.selectedPlayerIds = this.selectedPlayerIds.filter((rowId) => rowId !== id);
            } else {
                this.selectedPlayerIds = [...this.selectedPlayerIds, id];
            }
        },

        clearSelection() {
            this.selectedPlayerIds = [];
        },

        onFocus() {
            if (this.readOnly) {
                return;
            }
            this.open = true;
        },

        onInput(event) {
            if (this.readOnly) {
                return;
            }
            this.open = true;
            this.query = event.target.value;
        },

        close() {
            this.open = false;
        },

        clear() {
            this.query = '';
            this.selectedPlayerIds = [];
            this.open = false;
        },

        addSelected() {
            if (this.readOnly || this.selectedPlayerIds.length === 0) {
                return;
            }
            const players = this.selectedPlayerIds
                .map((id) => this.players.find((row) => Number(row.player_id) === Number(id)))
                .filter((row) => row && !this.isPlayerOnBoard(row.player_id));
            if (players.length === 0) {
                return;
            }
            Alpine.store('workingBoardBridge').add({
                boardType: this.boardType,
                round: this.round,
                players,
            });
            this.selectedPlayerIds = [];
            this.query = '';
            this.open = false;
        },

        addAllComplete() {
            if (this.readOnly) {
                return;
            }
            const players = this.players.filter(
                (p) => p.profile_complete && !this.isPlayerOnBoard(p.player_id),
            );
            if (players.length === 0) {
                return;
            }
            Alpine.store('workingBoardBridge').add({
                boardType: this.boardType,
                round: this.round,
                players,
            });
            this.selectedPlayerIds = [];
            this.query = '';
            this.open = false;
        },

        addAllAvailable() {
            if (this.readOnly) {
                return;
            }
            const players = this.availablePlayers();
            if (players.length === 0) {
                return;
            }
            Alpine.store('workingBoardBridge').add({
                boardType: this.boardType,
                round: this.round,
                players,
            });
            this.selectedPlayerIds = [];
            this.query = '';
            this.open = false;
        },
    }));

    Alpine.data('workingBoards', () => ({
        boardTypes: [],
        activeBoard: 'master',
        roundKeys: [],
        roundLabels: {},
        confidenceOptions: [],
        riskOptions: [],
        riskLabels: {},
        annotationTypes: [],
        annotationEditor: null,
        annotationEditorPosition: { top: 0, left: 0 },
        annotationTooltip: null,
        annotationTooltipPosition: { top: 0, left: 0 },
        boardRounds: {},
        boardPools: {},
        updateUrl: '',
        hsPlayerBaseUrl: '',
        ncaaPlayerBaseUrl: '',
        readOnly: false,
        batGradeBounds: { min: null, max: null, median: null },
        saving: false,
        saveError: '',
        statusMessage: '',
        statusIsError: false,
        _saveT: null,
        _statusT: null,
        _savingNow: false,
        _boardScaleT: null,
        _boardScaleObserver: null,
        _readyToSave: false,
        _boardDirty: false,

        init() {
            const config = readWorkingBoardConfig();
            this.boardTypes = Array.isArray(config.boardTypes) ? config.boardTypes : [];
            this.activeBoard = this.resolveActiveBoard();
            this.roundKeys = Array.isArray(config.roundKeys) ? config.roundKeys : [];
            this.roundLabels =
                config.roundLabels && typeof config.roundLabels === 'object' ? config.roundLabels : {};
            this.pickerRoundLabels =
                config.pickerRoundLabels && typeof config.pickerRoundLabels === 'object'
                    ? config.pickerRoundLabels
                    : {};
            this.confidenceOptions = Array.isArray(config.confidenceOptions) ? config.confidenceOptions : [];
            this.riskOptions = Array.isArray(config.riskOptions) ? config.riskOptions : [];
            this.riskLabels =
                config.riskLabels && typeof config.riskLabels === 'object' ? config.riskLabels : {};
            this.annotationTypes = Array.isArray(config.annotationTypes) ? config.annotationTypes : [];
            this.updateUrl = config.updateUrl ?? '';
            this.hsPlayerBaseUrl = String(config.hsPlayerBaseUrl ?? '').replace(/\/$/, '');
            this.ncaaPlayerBaseUrl = String(config.ncaaPlayerBaseUrl ?? '').replace(/\/$/, '');
            this.readOnly = Boolean(config.readOnly);
            this.batGradeBounds =
                config.batGradeBounds && typeof config.batGradeBounds === 'object'
                    ? config.batGradeBounds
                    : { min: null, max: null, median: null };

            Alpine.store('workingBoardBridge').register((detail) => this.addPlayerFromPicker(detail));
            Alpine.store('workingBoardBridge').setBoard(this);

            const boardsIn = config.boards && typeof config.boards === 'object' ? config.boards : {};
            this.boardRounds = {};
            this.boardPools = {};

            const masterBoard = boardsIn.master ?? {};
            this.boardPools.master = Array.isArray(masterBoard.playerPool) ? masterBoard.playerPool : [];
            const masterInit =
                masterBoard.initialRounds && typeof masterBoard.initialRounds === 'object'
                    ? masterBoard.initialRounds
                    : {};
            this.boardRounds.master = JSON.parse(JSON.stringify(masterInit));

            for (const rk of this.roundKeys) {
                if (!Array.isArray(this.boardRounds.master[rk])) {
                    this.boardRounds.master[rk] = [];
                }
                this.boardRounds.master[rk] = this.boardRounds.master[rk].filter(
                    (item) => !isNonTargetDivider(item) && (isRoundDivider(item) || item?.player_id),
                );
            }

            this.boardRounds = { ...this.boardRounds };
            this._readyToSave = true;
            this._boardDirty = false;

            // Best-effort persistence when navigating away quickly (only after user edits).
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.saveNow({ keepalive: true, silentSuccess: true });
                }
            });
            window.addEventListener('beforeunload', () => {
                this.saveNow({ keepalive: true, silentSuccess: true });
            });

            this.$watch('activeBoard', () => {
                this.$nextTick(() => this.scheduleBoardScale());
            });
            this.$watch('boardRounds', () => {
                this.scheduleBoardScale();
            });
            window.addEventListener('resize', () => this.scheduleBoardScale());
            this._boardScaleObserver = new ResizeObserver(() => this.scheduleBoardScale());
            document.querySelectorAll('.working-board-columns-viewport').forEach((el) => {
                this._boardScaleObserver.observe(el);
            });
            this.$nextTick(() => this.scheduleBoardScale());
        },

        scheduleBoardScale() {
            if (this._boardScaleT) {
                clearTimeout(this._boardScaleT);
            }
            this._boardScaleT = setTimeout(() => {
                this._boardScaleT = null;
                this.updateBoardScale();
            }, 50);
        },

        updateBoardScale() {
            const boardType = this.activeBoard;
            const pane = document.querySelector(`.working-board-pane[data-board-type="${boardType}"]`);
            if (!pane) {
                return;
            }
            const viewport = pane.querySelector('.working-board-columns-viewport');
            const content = pane.querySelector('.working-board-columns-scroll');
            if (!viewport || !content) {
                return;
            }

            content.style.transform = 'none';
            content.style.width = '100%';
            viewport.style.height = 'auto';

            const availH = viewport.clientHeight;
            const availW = viewport.clientWidth;
            if (availH <= 0 || availW <= 0) {
                return;
            }

            const naturalH = content.scrollHeight;
            const naturalW = content.scrollWidth;
            if (naturalH <= 0) {
                return;
            }

            const scale = Math.min(1, availH / naturalH, availW / naturalW);
            if (scale < 0.995) {
                content.style.transform = `scale(${scale})`;
                content.style.transformOrigin = 'top center';
                content.style.width = `${100 / scale}%`;
                viewport.style.height = `${Math.ceil(naturalH * scale)}px`;
            }
        },

        setStatus(message, isError = false) {
            this.statusMessage = message;
            this.statusIsError = isError;
            if (this._statusT) {
                clearTimeout(this._statusT);
            }
            if (message) {
                this._statusT = setTimeout(() => {
                    this.statusMessage = '';
                }, 5000);
            }
        },

        roundCards(boardType, rk) {
            return this.boardRounds?.[boardType]?.[rk] ?? [];
        },

        roundPlayerCount(boardType, rk) {
            return this.roundCards(boardType, rk).filter((item) => !isRoundDivider(item)).length;
        },

        isPassRoundKey(roundKey) {
            return isPassRoundKey(roundKey);
        },

        isBelowNonTargetDivider(boardType, rk, idx) {
            return isPassRoundKey(rk);
        },

        nonTargetDividerListIndex() {
            return -1;
        },

        isTierDivider(item) {
            return isTierDivider(item);
        },

        isNonTargetDivider(item) {
            return isNonTargetDivider(item);
        },

        isRoundDivider(item) {
            return isRoundDivider(item);
        },

        roundRowKey(boardType, rk, item, idx) {
            if (isNonTargetDivider(item)) {
                return `non-target-${boardType}-${rk}-${idx}`;
            }
            if (isTierDivider(item)) {
                return `tier-${boardType}-${rk}-${idx}`;
            }

            return `p-${boardType}-${rk}-${item?.player_id ?? 'x'}-${idx}`;
        },

        addTierDivider(boardType, rk) {
            if (this.readOnly) {
                return;
            }
            if (!Array.isArray(this.boardRounds[boardType]?.[rk])) {
                this.boardRounds[boardType][rk] = [];
            }
            const list = [...this.boardRounds[boardType][rk]];
            list.splice(list.length, 0, { entry_type: 'tier_divider' });
            this.boardRounds[boardType][rk] = list;
            this.boardRounds = { ...this.boardRounds };
            this.scheduleSave(50);
        },

        roundLabel(roundKey) {
            return (
                this.pickerRoundLabels?.[roundKey] ??
                this.roundLabels?.[roundKey] ??
                roundKey
            );
        },

        resolveActiveBoard() {
            const config = readWorkingBoardConfig();
            const visible = Array.isArray(config.visibleBoardTypes)
                ? config.visibleBoardTypes
                : Array.isArray(config.boardTypes)
                  ? config.boardTypes
                  : [];
            const preferred =
                typeof config.defaultActiveBoard === 'string' && config.defaultActiveBoard !== ''
                    ? config.defaultActiveBoard
                    : 'master';
            try {
                const saved = localStorage.getItem('workingBoardActive');
                if (saved && visible.includes(saved)) {
                    return saved;
                }
            } catch (_) {
                // localStorage may be unavailable
            }
            if (visible.includes(preferred)) {
                return preferred;
            }

            return visible[0] ?? preferred;
        },

        setActiveBoard(boardType) {
            if (!this.boardTypes.includes(boardType)) {
                return;
            }
            this.activeBoard = boardType;
            try {
                localStorage.setItem('workingBoardActive', boardType);
            } catch (_) {
                // localStorage may be unavailable
            }
            this.$nextTick(() => this.scheduleBoardScale());
        },

        addPlayerFromPicker(detail) {
            const boardType = detail?.boardType;
            const round = detail?.round;
            const player = detail?.player;
            const playerId = detail?.playerId;
            const players = detail?.players;

            if (!boardType || !round) {
                this.setStatus('Could not add player (missing board or round).', true);
                return;
            }

            if (Array.isArray(players) && players.length > 0) {
                this.addManyPlayersFromPicker(boardType, round, players);
                return;
            }

            if (player) {
                const added = this.addPlayerToRoundByTemplate(boardType, round, player);
                if (added) {
                    this.setStatus(`Added to round ${this.roundLabel(round)}.`);
                }
                return;
            }

            const id = Number(playerId);
            if (!id || Number.isNaN(id)) {
                this.setStatus('Could not add player (missing player id).', true);
                return;
            }

            const pool = this.boardPools[boardType] ?? [];
            const template = pool.find((p) => Number(p.player_id) === id);
            if (!template) {
                this.setStatus('Could not add player (not in pool).', true);
                return;
            }

            const added = this.addPlayerToRoundByTemplate(boardType, round, template);
            if (added) {
                this.setStatus(`Added to round ${this.roundLabel(round)}.`);
            }
        },

        addManyPlayersFromPicker(boardType, round, players) {
            let added = 0;
            let skipped = 0;
            for (const player of players) {
                if (this.addPlayerToRoundByTemplate(boardType, round, player, { silent: true })) {
                    added++;
                } else {
                    skipped++;
                }
            }
            if (added > 0) {
                this.scheduleSave(50);
                this.setStatus(
                    `Added ${added} to round ${this.roundLabel(round)}.${skipped > 0 ? ` ${skipped} skipped.` : ''}`,
                );
            } else {
                this.setStatus(
                    skipped > 0
                        ? 'All selected players are already on this board.'
                        : 'No players to add.',
                    true,
                );
            }
        },

        addPlayerToRoundByTemplate(boardType, rk, template, opts = {}) {
            const silent = Boolean(opts.silent);
            const id = Number(template?.player_id);
            if (!id || Number.isNaN(id)) {
                if (!silent) {
                    this.setStatus('Invalid player.', true);
                }
                return false;
            }
            if (this.isPlayerOnBoard(boardType, id)) {
                if (!silent) {
                    this.setStatus('Player is already on this board.', true);
                }
                return false;
            }
            const card = {
                ...template,
                confidence: '',
                risk: '',
                quick_take: '',
                separators: '',
                red_flags: '',
                dev_opportunities: '',
            };
            const rounds = { ...(this.boardRounds[boardType] ?? {}) };
            const list = [...(rounds[rk] ?? [])];
            list.push(card);
            rounds[rk] = list;
            this.boardRounds[boardType] = rounds;
            this.boardRounds = { ...this.boardRounds };
            if (!silent) {
                // Save quickly so navigating away doesn't lose board state.
                this.scheduleSave(50);
            }
            return true;
        },

        playerUrlForPool(pool, id) {
            const base = pool === 'ncaa' ? this.ncaaPlayerBaseUrl : this.hsPlayerBaseUrl;

            return `${base}/${encodeURIComponent(String(id))}`;
        },

        playerUrl(card) {
            return this.playerUrlForPool(card?.player_pool ?? 'hs', card?.player_id);
        },

        boardName(card) {
            const ln = String(card?.last_name ?? '').trim();
            const fn = String(card?.first_name ?? '').trim();
            if (ln === '' && fn === '') {
                return '—';
            }

            return `${ln.toUpperCase()}, ${fn.toUpperCase()}`;
        },

        hasAnnotation(card, key) {
            return String(card?.[key] ?? '').trim() !== '';
        },

        hasAnyAnnotation(card) {
            return this.annotationTypes.some((ann) => this.hasAnnotation(card, ann.key));
        },

        annotationText(card, key) {
            return String(card?.[key] ?? '').trim();
        },

        showAnnotationSummaryTooltip(event, card) {
            if (!this.hasAnyAnnotation(card)) {
                return;
            }
            const row = event?.currentTarget?.closest?.('tr[data-player-row]');
            this.positionAnnotationTooltip(row ?? event?.currentTarget);
            this.annotationTooltip = {
                rows: this.annotationTypes.map((ann) => ({
                    label: ann.shortLabel ?? ann.label,
                    text: this.annotationText(card, ann.key),
                })),
            };
        },

        hideAnnotationTooltip() {
            this.annotationTooltip = null;
        },

        positionAnnotationTooltip(anchor) {
            if (!anchor?.getBoundingClientRect) {
                return;
            }
            const rect = anchor.getBoundingClientRect();
            const width = 322;
            const height = 160;
            let top = rect.bottom + 6;
            let left = rect.left + rect.width / 2 - width / 2;
            if (top + height > window.innerHeight - 8) {
                top = Math.max(8, rect.top - height - 6);
            }
            left = Math.max(8, Math.min(left, window.innerWidth - width - 8));
            this.annotationTooltipPosition = { top, left };
        },

        annotationTooltipStyle() {
            const { top, left } = this.annotationTooltipPosition ?? { top: 0, left: 0 };

            return `top: ${top}px; left: ${left}px;`;
        },

        isAnnotationPickerOpen(boardType, rk, idx) {
            const editor = this.annotationEditor;
            if (!editor) {
                return false;
            }

            return (
                editor.boardType === boardType &&
                editor.rk === rk &&
                Number(editor.idx) === Number(idx)
            );
        },

        positionAnnotationEditor(anchor) {
            if (!anchor?.getBoundingClientRect) {
                return;
            }
            const rect = anchor.getBoundingClientRect();
            const width = 208;
            const height = 240;
            let top = rect.bottom + 4;
            let left = rect.right - width;
            if (top + height > window.innerHeight - 8) {
                top = Math.max(8, rect.top - height - 4);
            }
            left = Math.max(8, Math.min(left, window.innerWidth - width - 8));
            this.annotationEditorPosition = { top, left };
        },

        annotationEditorStyle() {
            const { top, left } = this.annotationEditorPosition ?? { top: 0, left: 0 };

            return `top: ${top}px; left: ${left}px;`;
        },

        openAnnotationPicker(boardType, rk, idx, event) {
            if (this.readOnly) {
                return;
            }
            this.hideAnnotationTooltip();
            const card = this.boardRounds?.[boardType]?.[rk]?.[idx];
            if (!card || isRoundDivider(card)) {
                return;
            }
            this.positionAnnotationEditor(event?.currentTarget);
            this.annotationEditor = {
                boardType,
                rk,
                idx,
                key: '',
                label: '',
                icon: '',
                draft: '',
            };
        },

        openAnnotationEditor(boardType, rk, idx, ann, event) {
            if (this.readOnly) {
                return;
            }
            this.hideAnnotationTooltip();
            const card = this.boardRounds?.[boardType]?.[rk]?.[idx];
            if (!card || isRoundDivider(card)) {
                return;
            }
            this.positionAnnotationEditor(event?.currentTarget);
            this.annotationEditor = {
                boardType,
                rk,
                idx,
                key: ann.key,
                label: ann.label,
                icon: ann.icon,
                draft: this.annotationText(card, ann.key),
            };
        },

        selectAnnotationType(key) {
            const editor = this.annotationEditor;
            if (!editor) {
                return;
            }
            const ann = this.annotationTypes.find((item) => item.key === key);
            if (!ann) {
                return;
            }
            const card = this.boardRounds?.[editor.boardType]?.[editor.rk]?.[editor.idx];
            if (!card || isRoundDivider(card)) {
                return;
            }
            editor.key = ann.key;
            editor.label = ann.label;
            editor.icon = ann.icon;
            editor.draft = this.annotationText(card, ann.key);
        },

        annotationTypeIsSelected(key) {
            return this.annotationEditor?.key === key;
        },

        canSaveAnnotationEditor() {
            const editor = this.annotationEditor;
            if (!editor?.key) {
                return false;
            }

            return String(editor.draft ?? '').trim() !== '';
        },

        canClearAnnotationEditor() {
            const editor = this.annotationEditor;
            if (!editor?.key) {
                return false;
            }
            const card = this.boardRounds?.[editor.boardType]?.[editor.rk]?.[editor.idx];
            if (!card || isRoundDivider(card)) {
                return false;
            }

            return this.hasAnnotation(card, editor.key);
        },

        closeAnnotationEditor() {
            this.annotationEditor = null;
        },

        saveAnnotationEditor() {
            const editor = this.annotationEditor;
            if (!editor?.key || !this.canSaveAnnotationEditor()) {
                return;
            }
            const card = this.boardRounds?.[editor.boardType]?.[editor.rk]?.[editor.idx];
            if (!card || isRoundDivider(card)) {
                this.closeAnnotationEditor();
                return;
            }
            card[editor.key] = String(editor.draft ?? '').trim();
            this.boardRounds = { ...this.boardRounds };
            this.closeAnnotationEditor();
            this.scheduleSave(50);
        },

        clearAnnotationEditor() {
            const editor = this.annotationEditor;
            if (!editor) {
                return;
            }
            const card = this.boardRounds?.[editor.boardType]?.[editor.rk]?.[editor.idx];
            if (!card || isRoundDivider(card)) {
                this.closeAnnotationEditor();
                return;
            }
            card[editor.key] = '';
            this.boardRounds = { ...this.boardRounds };
            this.closeAnnotationEditor();
            this.scheduleSave(50);
        },

        gradeFmt(v) {
            if (v === null || v === undefined || v === '') {
                return '—';
            }
            const n = Number(v);

            return Number.isNaN(n) ? '—' : n.toFixed(1);
        },

        gradeFieldBounds(boardType, field) {
            return gradeFieldPercentileBounds(this.boardRounds, boardType, this.roundKeys, field);
        },

        roleCellStyle(card) {
            return gradeScaleBoardCellStyle(card?.grade_role);
        },

        swingCellStyle(card) {
            return gradeScaleBoardCellStyle(card?.grade_swing);
        },

        batGrade(card) {
            return batGradeForCard(card);
        },

        batBounds() {
            return this.batGradeBounds;
        },

        batCellStyle(card) {
            const value = batGradeForCard(card);

            return batGradePercentileCellStyle(value, this.batBounds());
        },

        scaleLabel(v) {
            if (v === '' || v === null || v === undefined) {
                return '—';
            }

            return String(v);
        },

        confidenceLabel(v) {
            return this.scaleLabel(v);
        },

        riskLabel(v) {
            if (v === '' || v === null || v === undefined) {
                return '—';
            }

            return this.riskLabels?.[String(v)] ?? String(v);
        },

        boardScaleFillStyle(v) {
            return boardScaleFillStyle(v);
        },

        openScaleSelect(event) {
            if (this.readOnly) {
                return;
            }
            const select = event.currentTarget?.querySelector?.('select');
            if (!select || select.disabled) {
                return;
            }
            if (typeof select.showPicker === 'function') {
                try {
                    select.showPicker();
                    return;
                } catch {
                    // fall through
                }
            }
            select.focus();
            select.click();
        },

        isPlayerOnBoard(boardType, pid) {
            const id = Number(pid);
            const rounds = this.boardRounds[boardType] ?? {};
            for (const rk of this.roundKeys) {
                const list = rounds[rk] ?? [];
                for (const c of list) {
                    if (Number(c.player_id) === id) {
                        return true;
                    }
                }
            }

            return false;
        },

        poolOptions(boardType) {
            const pool = this.boardPools[boardType] ?? [];

            return pool
                .filter((p) => !this.isPlayerOnBoard(boardType, p.player_id))
                .map((p) => ({
                    player_id: p.player_id,
                    player_pool: p.player_pool ?? 'hs',
                    label: this.boardName(p),
                }));
        },

        addPlayerToRoundById(boardType, rk, playerId) {
            const id = Number(playerId);
            if (!id || Number.isNaN(id) || this.isPlayerOnBoard(boardType, id)) {
                return;
            }
            const pool = this.boardPools[boardType] ?? [];
            const template = pool.find((p) => Number(p.player_id) === id);
            if (!template) {
                return;
            }
            const added = this.addPlayerToRoundByTemplate(boardType, rk, template);
            if (added) {
                this.scheduleSave();
            }
        },

        removeFromRound(boardType, rk, idx) {
            if (!Array.isArray(this.boardRounds[boardType]?.[rk])) {
                return;
            }
            if (isNonTargetDivider(this.boardRounds[boardType][rk][idx])) {
                return;
            }
            this.boardRounds[boardType][rk].splice(idx, 1);
            this.scheduleSave();
        },

        onDragStart(ev, boardType, rk, idx) {
            if (this.readOnly) {
                return;
            }
            const list = this.boardRounds[boardType]?.[rk];
            if (!Array.isArray(list) || idx < 0 || idx >= list.length) {
                return;
            }
            if (isNonTargetDivider(list[idx])) {
                ev.preventDefault();
                return;
            }
            const payload = JSON.stringify({ boardType, rk, idx });
            ev.dataTransfer.setData('application/x-working-board', payload);
            ev.dataTransfer.setData('text/plain', payload);
            ev.dataTransfer.effectAllowed = 'move';
        },

        dropIndexFromEvent(ev, list) {
            const tbody = ev.currentTarget;
            const rows = [...tbody.querySelectorAll('tr[data-board-list-row]')];
            const y = ev.clientY;

            if (rows.length === 0) {
                return 0;
            }

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const rect = row.getBoundingClientRect();
                const mid = rect.top + rect.height / 2;
                if (y < mid) {
                    return i;
                }
            }

            return Array.isArray(list) ? list.length : rows.length;
        },

        onRoundDrop(ev, boardType, targetRk) {
            let raw = ev.dataTransfer.getData('application/x-working-board');
            if (!raw) {
                raw = ev.dataTransfer.getData('text/plain');
            }
            if (!raw) {
                return;
            }
            let payload;
            try {
                payload = JSON.parse(raw);
            } catch {
                return;
            }
            if (payload.boardType !== boardType) {
                return;
            }
            const fromRk = payload.rk;
            const fromIdx = Number(payload.idx);
            const targetList = this.boardRounds[boardType]?.[targetRk] ?? [];
            let insertAt = this.dropIndexFromEvent(ev, targetList);
            const list = this.boardRounds[boardType]?.[fromRk];
            if (!Array.isArray(list) || fromIdx < 0 || fromIdx >= list.length) {
                return;
            }
            const [card] = list.splice(fromIdx, 1);
            if (fromRk === targetRk && insertAt > fromIdx) {
                insertAt -= 1;
            }
            this.boardRounds[boardType][fromRk] = list;
            if (!Array.isArray(this.boardRounds[boardType][targetRk])) {
                this.boardRounds[boardType][targetRk] = [];
            }
            insertAt = Math.max(0, Math.min(insertAt, this.boardRounds[boardType][targetRk].length));
            this.boardRounds[boardType][targetRk].splice(insertAt, 0, card);
            this.boardRounds = { ...this.boardRounds };
            this.scheduleSave();
        },

        buildPayload() {
            const boards = {};
            for (const boardType of this.boardTypes) {
                const rounds = {};
                for (const rk of this.roundKeys) {
                    rounds[rk] = (this.boardRounds[boardType]?.[rk] ?? []).map((c) => {
                        if (isNonTargetDivider(c)) {
                            return { entry_type: 'non_target_divider' };
                        }
                        if (isTierDivider(c)) {
                            return { entry_type: 'tier_divider' };
                        }

                        return {
                            entry_type: 'player',
                            player_id: Number(c.player_id),
                            confidence: c.confidence ?? '',
                            risk: c.risk ?? '',
                            quick_take: c.quick_take ?? '',
                            separators: c.separators ?? '',
                            red_flags: c.red_flags ?? '',
                            dev_opportunities: c.dev_opportunities ?? '',
                        };
                    });
                }
                boards[boardType] = { rounds };
            }

            return { boards };
        },

        scheduleSave(delayMs = 400) {
            if (this.readOnly) {
                return;
            }
            this._boardDirty = true;
            this.saveError = '';
            if (this._saveT) {
                clearTimeout(this._saveT);
            }
            this._saveT = setTimeout(() => {
                this.saveNow();
            }, delayMs);
        },

        async saveNow(opts = {}) {
            if (!this.updateUrl || !this._readyToSave) {
                return;
            }
            if (!this._boardDirty && !opts.force) {
                return;
            }
            if (this._savingNow) {
                return;
            }
            this._savingNow = true;
            this.saving = true;
            this.saveError = '';
            try {
                const payload = this.buildPayload();
                const keepalive = Boolean(opts.keepalive);

                if (keepalive) {
                    const token = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') ?? '';
                    await fetch(this.updateUrl, {
                        method: 'PATCH',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                        },
                        body: JSON.stringify(payload),
                        keepalive: true,
                        credentials: 'same-origin',
                    });
                } else {
                    await window.axios.patch(this.updateUrl, payload, {
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                }

                if (!opts.silentSuccess) {
                    this.setStatus('Saved.', false);
                }
                this._boardDirty = false;
            } catch (e) {
                const msg =
                    e?.response?.data?.message ??
                    e?.response?.data?.errors?.['boards.hs.rounds']?.[0] ??
                    e?.response?.data?.errors?.boards?.[0] ??
                    'Could not save board.';
                this.saveError = typeof msg === 'string' ? msg : 'Could not save board.';
                this.setStatus(this.saveError, true);
            } finally {
                this.saving = false;
                this._savingNow = false;
            }
        },
    }));
});

Alpine.start();
