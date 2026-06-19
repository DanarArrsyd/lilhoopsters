import './bootstrap';
import { Chart, BarController, LineController, BarElement, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Filler } from 'chart.js';

Chart.register(BarController, LineController, BarElement, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Filler);

document.addEventListener('alpine:init', () => {
    Alpine.data('revenueChart', (initialLabels = [], initialAmounts = [], initialAvg = 0) => ({
        chartInstance: null,
        _handler: null,

        init() {
            this.createChart(initialLabels, initialAmounts, initialAvg);

            this._handler = (e) => {
                const { labels, amounts, avg } = e.detail;
                this.createChart(labels, amounts, avg);
            };
            window.addEventListener('chart-data-ready', this._handler);
        },

        destroy() {
            if (this._handler) window.removeEventListener('chart-data-ready', this._handler);
            if (this.chartInstance) { this.chartInstance.destroy(); this.chartInstance = null; }
        },

        createChart(labels, amounts, avg) {
            if (this.chartInstance) { this.chartInstance.destroy(); this.chartInstance = null; }

            const canvas = this.$el.querySelector('canvas');
            if (!canvas || !labels.length) return;

            const navyFill   = '#0A0F1E';
            const mutedFill  = '#94a3b8';
            const hoverFill  = '#1D4ED8';

            const bgColors = amounts.map(v => (avg > 0 && v >= avg) ? navyFill : mutedFill);

            this.chartInstance = new Chart(canvas, {
                data: {
                    labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Revenue',
                            data: amounts,
                            backgroundColor: bgColors,
                            hoverBackgroundColor: hoverFill,
                            borderRadius: { topLeft: 4, topRight: 4 },
                            borderSkipped: false,
                            order: 2,
                        },
                        {
                            type: 'line',
                            label: 'Rata-rata',
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
