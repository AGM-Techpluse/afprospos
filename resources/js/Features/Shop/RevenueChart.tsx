import { CategoryScale, Chart as ChartJS, Filler, LinearScale, LineElement, PointElement, Tooltip } from 'chart.js';
import { Line } from 'react-chartjs-2';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Filler);

export type RevenuePoint = { date: string; revenue_minor: number };

function formatNaira(minor: number): string {
    return `₦${(minor / 100).toLocaleString('en-NG', { minimumFractionDigits: 0 })}`;
}

function formatShortDate(isoDate: string): string {
    return new Date(`${isoDate}T00:00:00`).toLocaleDateString('en-NG', { month: 'short', day: 'numeric' });
}

/** Daily revenue over the trailing window, backed by Chart.js (MIT-licensed, chartjs.org) via react-chartjs-2. */
export default function RevenueChart({ series }: { series: RevenuePoint[] }) {
    const data = {
        labels: series.map((point) => formatShortDate(point.date)),
        datasets: [
            {
                label: 'Revenue',
                data: series.map((point) => point.revenue_minor / 100),
                borderColor: '#1E56E8',
                backgroundColor: 'rgba(30, 86, 232, 0.08)',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointBackgroundColor: '#1E56E8',
            },
        ],
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (context: { parsed: { y: number } }) => formatNaira(Math.round(context.parsed.y * 100)),
                },
            },
        },
        scales: {
            x: { grid: { display: false } },
            y: {
                beginAtZero: true,
                ticks: { callback: (value: number | string) => formatNaira(Math.round(Number(value) * 100)) },
            },
        },
    };

    return (
        <div className="h-64">
            <Line data={data} options={options} />
        </div>
    );
}
