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
                backgroundColor: 'rgba(79, 70, 229, 0.7)',
                borderRadius: 4,
            }],
        },
        options: {
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
