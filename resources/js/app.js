import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
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
