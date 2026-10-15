import { CategoryScale, Chart as ChartJS, Filler, LinearScale, LineElement, PointElement, Tooltip, TooltipItem } from 'chart.js';
import { Line } from 'react-chartjs-2';
import { REVENUE_STREAM_COLORS } from './revenueStreamColors';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Filler);

export type RevenuePoint = { date: string; sales_minor: number; repair_minor: number };

const SALES_COLOR = REVENUE_STREAM_COLORS.sales_checkout;
const REPAIR_COLOR = REVENUE_STREAM_COLORS.repair_job;

function formatNaira(minor: number): string {
    return `₦${(minor / 100).toLocaleString('en-NG', { minimumFractionDigits: 0 })}`;
}

function formatShortDate(isoDate: string): string {
    return new Date(`${isoDate}T00:00:00`).toLocaleDateString('en-NG', { month: 'short', day: 'numeric' });
}

/** Daily revenue over the trailing window, split by stream (Sales vs Repairs) — same fixed colors as the revenue-by-stream pie chart. Backed by Chart.js via react-chartjs-2. */
export default function RevenueChart({ series }: { series: RevenuePoint[] }) {
    const data = {
        labels: series.map((point) => formatShortDate(point.date)),
        datasets: [
            {
                label: 'Sales',
                data: series.map((point) => point.sales_minor / 100),
                borderColor: SALES_COLOR,
                backgroundColor: 'rgba(22, 163, 74, 0.08)',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointBackgroundColor: SALES_COLOR,
            },
            {
                label: 'Repairs',
                data: series.map((point) => point.repair_minor / 100),
                borderColor: REPAIR_COLOR,
                backgroundColor: 'rgba(30, 86, 232, 0.08)',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointBackgroundColor: REPAIR_COLOR,
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
                    label: (context: TooltipItem<'line'>) =>
                        `${context.dataset.label}: ${formatNaira(Math.round((context.parsed.y ?? 0) * 100))}`,
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
        <div className="w-full min-w-0">
            <ul className="flex items-center gap-4 text-sm mb-2">
                <li className="flex items-center gap-1.5">
                    <span className="h-2.5 w-2.5 rounded-full shrink-0" style={{ backgroundColor: SALES_COLOR }} aria-hidden="true" />
                    <span className="text-admin-text2">Sales</span>
                </li>
                <li className="flex items-center gap-1.5">
                    <span className="h-2.5 w-2.5 rounded-full shrink-0" style={{ backgroundColor: REPAIR_COLOR }} aria-hidden="true" />
                    <span className="text-admin-text2">Repairs</span>
                </li>
            </ul>
            <div className="relative h-60 w-full min-w-0">
                <Line data={data} options={options} />
            </div>
        </div>
    );
}
