import Chart from 'chart.js/auto';

// Data is rendered server-side into a data attribute (escaped by Blade), no inline script needed.
const canvas = document.getElementById('tickets-per-day');

if (canvas) {
    const { labels, data, label } = JSON.parse(canvas.dataset.chart);

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label,
                data,
                backgroundColor: '#0E5E6F', // brand-700 (tailwind.config.js)
                hoverBackgroundColor: '#0A4652',
                borderRadius: 4,
            }],
        },
        options: {
            // Respect the "reduce motion" system setting (vestibular disorders).
            animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : undefined,
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { grid: { display: false } },
            },
        },
    });
}
