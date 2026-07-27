import './bootstrap';
import Chart from 'chart.js/auto';
import collapse from '@alpinejs/collapse';

document.addEventListener('alpine:init', () => {
    Alpine.plugin(collapse);

    // Custom listbox that wraps a real <select>. The native element stays in the
    // DOM as the single source of truth, so `wire:model`, `wire:change`, form
    // submission and validation all keep working untouched — this only replaces
    // the OS-rendered popup, which we can't style and which reads badly on a
    // dense admin form. Without JS the native select is still there and usable.
    Alpine.data('hoopSelect', (config = {}) => ({
        open: false,
        options: [],
        value: '',
        activeIndex: -1,
        query: '',
        typeBuffer: '',
        typeTimer: null,
        observer: null,
        reposition: null,
        // Above this many options a filter field appears in the panel. Picking a
        // location out of 30 by scrolling is the thing admins complain about.
        searchThreshold: config.searchThreshold ?? 8,

        init() {
            // Progressive enhancement switch: CSS keeps the native select visible
            // and the custom trigger hidden until this flag lands, so a JS failure
            // degrades to a plain working <select> instead of an unusable field.
            this.$root.dataset.enhanced = 'true';

            this.refresh();

            // Livewire re-renders rewrite the <option> list (programs filtered by
            // type, locations by search…). Mirror those edits instead of caching
            // a stale list from init.
            this.observer = new MutationObserver(() => this.refresh());
            this.observer.observe(this.$refs.native, {
                childList: true, subtree: true, characterData: true,
                attributes: true, attributeFilter: ['value', 'disabled'],
            });

            this.$refs.native.addEventListener('change', () => this.refresh());
        },

        destroy() {
            this.observer?.disconnect();
            this.detachReposition();
        },

        refresh() {
            const native = this.$refs.native;
            if (!native) return;

            this.options = Array.from(native.options).map((o, i) => ({
                index: i,
                value: o.value,
                label: o.textContent.trim(),
                disabled: o.disabled,
                // An empty value is the "Select a location…" row every form here
                // already ships. Treat it as the placeholder, not a real choice.
                placeholder: o.value === '',
            }));

            this.value = native.value;
        },

        get isDisabled() { return this.$refs.native?.disabled || config.loading === true; },

        get selected() { return this.options.find(o => o.value === this.value) || null; },

        get triggerLabel() {
            const sel = this.selected;
            if (!sel || sel.placeholder) {
                return this.options.find(o => o.placeholder)?.label || 'Select…';
            }
            return sel.label;
        },

        get showsPlaceholder() {
            const sel = this.selected;
            return !sel || sel.placeholder;
        },

        get searchable() { return this.options.length > this.searchThreshold; },

        get visibleOptions() {
            const q = this.query.trim().toLowerCase();
            const real = this.options.filter(o => !o.placeholder);
            if (!q) return real;
            return real.filter(o => o.label.toLowerCase().includes(q));
        },

        optionId(option) { return `${this.$id('hoop-select')}-opt-${option.index}`; },

        get activeDescendant() {
            const active = this.visibleOptions[this.activeIndex];
            return active ? this.optionId(active) : null;
        },

        toggle() { this.open ? this.close() : this.show(); },

        show() {
            if (this.isDisabled) return;
            this.refresh();
            this.query = '';
            this.open = true;

            const current = this.visibleOptions.findIndex(o => o.value === this.value);
            this.activeIndex = current >= 0 ? current : 0;

            this.$nextTick(() => {
                this.position();
                this.attachReposition();
                if (this.searchable) this.$refs.search?.focus();
                this.scrollActiveIntoView();
            });
        },

        close(refocus = true) {
            if (!this.open) return;
            this.open = false;
            this.activeIndex = -1;
            this.detachReposition();
            if (refocus) this.$refs.trigger?.focus();
        },

        choose(option) {
            if (!option || option.disabled) return;

            const native = this.$refs.native;
            native.value = option.value;
            this.value = option.value;

            // Livewire binds `change` on selects; `input` covers anything reading
            // the field generically. Both bubble so wire:model/wire:change fire.
            native.dispatchEvent(new Event('input', { bubbles: true }));
            native.dispatchEvent(new Event('change', { bubbles: true }));

            this.close();
        },

        move(delta) {
            const list = this.visibleOptions;
            if (!list.length) return;

            let next = this.activeIndex;
            for (let step = 0; step < list.length; step++) {
                next = (next + delta + list.length) % list.length;
                if (!list[next].disabled) break;
            }
            this.activeIndex = next;
            this.scrollActiveIntoView();
        },

        moveTo(edge) {
            const list = this.visibleOptions;
            if (!list.length) return;
            this.activeIndex = edge === 'first' ? 0 : list.length - 1;
            this.scrollActiveIntoView();
        },

        // Type-ahead for the short lists that have no filter field — matches the
        // native select behaviour people already have in their fingers.
        typeAhead(key) {
            if (this.searchable) return;

            clearTimeout(this.typeTimer);
            this.typeBuffer += key.toLowerCase();
            this.typeTimer = setTimeout(() => { this.typeBuffer = ''; }, 500);

            const hit = this.visibleOptions.findIndex(o => o.label.toLowerCase().startsWith(this.typeBuffer));
            if (hit >= 0) {
                this.activeIndex = hit;
                this.scrollActiveIntoView();
            }
        },

        scrollActiveIntoView() {
            this.$nextTick(() => {
                const el = this.$refs.panel?.querySelector('[data-active="true"]');
                el?.scrollIntoView({ block: 'nearest' });
            });
        },

        // Fixed positioning against the trigger's rect. The panel is teleported to
        // <body> because several of these selects live inside modal cards with
        // `overflow-hidden`, which would otherwise clip the popup.
        position() {
            const trigger = this.$refs.trigger;
            const panel   = this.$refs.panel;
            if (!trigger || !panel) return;

            const rect   = trigger.getBoundingClientRect();
            const gap    = 6;
            const margin = 12;
            const below  = window.innerHeight - rect.bottom - gap - margin;
            const above  = rect.top - gap - margin;
            const flip   = below < 200 && above > below;
            const room   = Math.max(140, Math.min(320, flip ? above : below));

            panel.style.position  = 'fixed';
            panel.style.left      = `${rect.left}px`;
            panel.style.width     = `${rect.width}px`;
            panel.style.maxHeight = `${room}px`;

            if (flip) {
                panel.style.top    = 'auto';
                panel.style.bottom = `${window.innerHeight - rect.top + gap}px`;
            } else {
                panel.style.bottom = 'auto';
                panel.style.top    = `${rect.bottom + gap}px`;
            }
        },

        attachReposition() {
            this.reposition = () => this.position();
            window.addEventListener('scroll', this.reposition, true);
            window.addEventListener('resize', this.reposition);
        },

        detachReposition() {
            if (!this.reposition) return;
            window.removeEventListener('scroll', this.reposition, true);
            window.removeEventListener('resize', this.reposition);
            this.reposition = null;
        },

        onDocumentPointer(event) {
            if (!this.open) return;
            const t = event.target;
            if (this.$refs.trigger?.contains(t) || this.$refs.panel?.contains(t)) return;
            this.close(false);
        },
    }));

    Alpine.data('revenueChart', (initialLabels = [], initialAmounts = [], initialAvg = 0) => ({
        chartInstance: null,
        _handler: null,
        // Latest data. The Livewire `chart-data-ready` event can fire
        // synchronously during init() — BEFORE $nextTick / before the canvas
        // is laid out. Creating a chart then gives a null 2D context and
        // crashes with "null is not an object (n.save)". So the event NEVER
        // creates the chart; it only stashes data or updates an existing one.
        _data: { labels: initialLabels, amounts: initialAmounts, avg: initialAvg },

        init() {
            this._handler = (e) => {
                const { labels, amounts, avg } = e.detail;
                this._data = { labels, amounts, avg };
                if (this.chartInstance) this.updateChart();
            };
            window.addEventListener('chart-data-ready', this._handler);

            // Defer creation until the canvas is in the DOM and laid out.
            this.$nextTick(() => this.createChart());
        },

        destroy() {
            if (this._handler) window.removeEventListener('chart-data-ready', this._handler);
            if (this.chartInstance) { this.chartInstance.destroy(); this.chartInstance = null; }
        },

        barColors(amounts, avg) {
            return amounts.map(v => (avg > 0 && v >= avg) ? '#0A0F1E' : '#94a3b8');
        },

        updateChart() {
            if (!this.chartInstance) return;
            const { labels, amounts, avg } = this._data;
            const c = this.chartInstance;
            c.data.labels = labels;
            c.data.datasets[0].data = amounts;
            c.data.datasets[0].backgroundColor = this.barColors(amounts, avg);
            c.data.datasets[1].data = amounts.map(() => avg);
            c.update();
        },

        createChart() {
            if (this.chartInstance) { this.chartInstance.destroy(); this.chartInstance = null; }

            const canvas = this.$el.querySelector('canvas');
            const { labels, amounts, avg } = this._data;
            if (!canvas || !canvas.getContext('2d') || !labels.length) return;

            const bgColors = this.barColors(amounts, avg);

            this.chartInstance = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Revenue',
                            data: amounts,
                            backgroundColor: bgColors,
                            hoverBackgroundColor: '#1D4ED8',
                            borderRadius: { topLeft: 4, topRight: 4 },
                            borderSkipped: false,
                            order: 2,
                        },
                        {
                            type: 'line',
                            label: 'Average',
                            data: amounts.map(() => avg),
                            borderColor: '#B45309',
                            borderDash: [6, 3],
                            borderWidth: 1.5,
                            pointRadius: 0,
                            fill: false,
                            tension: 0,
                            order: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    animation: { duration: 400, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0A0F1E',
                            titleColor: 'rgba(255,255,255,0.5)',
                            bodyColor: '#ffffff',
                            titleFont: { family: 'Arial', size: 10, weight: 'normal' },
                            bodyFont: { family: 'Arial', size: 13, weight: 'bold' },
                            padding: 12,
                            cornerRadius: 10,
                            displayColors: false,
                            callbacks: {
                                title: (items) => items[0]?.label ?? '',
                                label: (ctx) => {
                                    if (ctx.datasetIndex === 1) return null;
                                    return 'Rp ' + (ctx.raw ?? 0).toLocaleString('id-ID');
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: { dash: [4, 3], display: false },
                            grid: { color: '#E6E3DC' },
                            ticks: {
                                color: '#9AA0AC',
                                font: { family: 'Arial', size: 10 },
                                callback(val) {
                                    if (val === 0) return '0';
                                    if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1).replace('.', ',') + 'jt';
                                    if (val >= 1000)    return 'Rp ' + (val / 1000) + 'rb';
                                    return 'Rp ' + val;
                                },
                            },
                        },
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                color: '#9AA0AC',
                                font: { family: 'Arial', size: 10 },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 12,
                            },
                        },
                    },
                },
            });
        },
    }));
});
