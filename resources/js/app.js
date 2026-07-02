import './bootstrap';
import Chart from 'chart.js/auto';
import collapse from '@alpinejs/collapse';

document.addEventListener('alpine:init', () => {
    Alpine.plugin(collapse);

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
