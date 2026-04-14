import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
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
});

Alpine.start();
