import { ArcElement, BarElement, CategoryScale, Chart as ChartJS, Filler, LinearScale, LineElement, PointElement, Tooltip } from 'chart.js';
import { Bar, Doughnut, Line } from 'react-chartjs-2';
import { formatNaira } from '../../lib/money';
import { BreakdownPoint, TrendPoint } from './types';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Tooltip, Filler);

const COLORS = ['#1E56E8', '#16A34A', '#D97706', '#DC2626', '#7C3AED', '#0891B2', '#667085'];

export function DoughnutBreakdown({ rows, money = false }: { rows: BreakdownPoint[]; money?: boolean }) {
    const values = rows.map((row) => money ? (row.value_minor ?? 0) : (row.value ?? row.count ?? 0));
    const total = values.reduce((sum, value) => sum + value, 0);

    if (total === 0) {
        return <div className="h-64 flex items-center justify-center text-sm text-admin-text3">No data for this period.</div>;
    }

    return (
        <div className="grid gap-3 min-w-0">
            <div className="relative h-44 sm:h-48 max-w-[240px] w-full mx-auto">
                <Doughnut
                    data={{
                        labels: rows.map((row) => row.label),
                        datasets: [{ data: values, backgroundColor: rows.map((_, i) => COLORS[i % COLORS.length]), borderWidth: 2, borderColor: '#FFFFFF' }],
                    }}
                    options={{ responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { display: false } } }}
                />
            </div>
            <ul className="grid grid-cols-2 gap-x-4 gap-y-2 min-w-0">
                {rows.map((row, i) => {
                    const value = values[i];
                    return (
                        <li key={row.label} className="flex items-center gap-2 text-xs min-w-0">
                            <span className="h-2.5 w-2.5 rounded-full shrink-0" style={{ backgroundColor: COLORS[i % COLORS.length] }} />
                            <span className="text-admin-text truncate">{row.label.replace(/_/g, ' ')}</span>
                            <span className="ml-auto text-admin-text2 font-mono shrink-0">{money ? formatNaira(value) : value.toLocaleString()}</span>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

export function TrendLine({ rows, series }: { rows: TrendPoint[]; series: { key: string; label: string; money?: boolean }[] }) {
    return (
        <div className="relative h-72">
            <Line
                data={{
                    labels: rows.map((row) => new Date(`${row.date}T00:00:00`).toLocaleDateString('en-NG', { month: 'short', day: 'numeric' })),
                    datasets: series.map((item, i) => ({
                        label: item.label,
                        data: rows.map((row) => Number(row[item.key] ?? 0) / (item.money ? 100 : 1)),
                        borderColor: COLORS[i % COLORS.length],
                        backgroundColor: `${COLORS[i % COLORS.length]}14`,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2,
                    })),
                }}
                options={{ responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } }}
            />
        </div>
    );
}

export function HorizontalBar({ rows, labelKey = 'label', valueKey = 'value', money = false }: { rows: Record<string, any>[]; labelKey?: string; valueKey?: string; money?: boolean }) {
    return (
        <div className="relative h-72">
            <Bar
                data={{
                    labels: rows.map((row) => String(row[labelKey]).replace(/_/g, ' ')),
                    datasets: [{ data: rows.map((row) => Number(row[valueKey] ?? 0) / (money ? 100 : 1)), backgroundColor: COLORS[0], borderRadius: 4 }],
                }}
                options={{
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (context) => money ? formatNaira(Math.round(Number(context.parsed.x) * 100)) : String(context.parsed.x) } } },
                    scales: { x: { beginAtZero: true }, y: { grid: { display: false } } },
                }}
            />
        </div>
    );
}
