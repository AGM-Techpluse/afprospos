import { ArcElement, Chart as ChartJS, Tooltip } from 'chart.js';
import { Doughnut } from 'react-chartjs-2';

ChartJS.register(ArcElement, Tooltip);

export type RevenueBreakdownSlice = { stream: string; label: string; revenue_minor: number };

// Matches the existing Dashboard quick-action color convention (Repairs =
// blue, Sales = green) rather than inventing a new mapping. Validated as a
// colorblind-safe categorical pair (dataviz skill: CVD ΔE 31.7, normal-vision
// ΔE 35.4, both well above the ΔE >= 8 floor). A third stream (e.g. trade-ins)
// needs its own hue re-validated against this pair before it's added here —
// the fixed order below must never just cycle in a new color.
const STREAM_COLORS: Record<string, string> = {
    repair_job: '#1E56E8',
    sales_checkout: '#16A34A',
};
const FALLBACK_COLOR = '#98A2B3';

function formatNaira(minor: number): string {
    return `₦${(minor / 100).toLocaleString('en-NG', { minimumFractionDigits: 0 })}`;
}

/** Revenue share by stream (Sales vs Repairs today, extensible to more), backed by Chart.js via react-chartjs-2. */
export default function RevenueBreakdownChart({ breakdown }: { breakdown: RevenueBreakdownSlice[] }) {
    const total = breakdown.reduce((sum, slice) => sum + slice.revenue_minor, 0);

    if (total === 0) {
        return (
            <div className="h-64 flex items-center justify-center">
                <p className="text-sm text-admin-text3">No confirmed revenue yet.</p>
            </div>
        );
    }

    const colors = breakdown.map((slice) => STREAM_COLORS[slice.stream] ?? FALLBACK_COLOR);

    const data = {
        labels: breakdown.map((slice) => slice.label),
        datasets: [
            {
                data: breakdown.map((slice) => slice.revenue_minor),
                backgroundColor: colors,
                borderColor: '#FFFFFF',
                borderWidth: 2,
            },
        ],
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (context: { label: string; parsed: number }) =>
                        `${context.label}: ${formatNaira(context.parsed)} (${Math.round((context.parsed / total) * 100)}%)`,
                },
            },
        },
    };

    return (
        <div className="h-64 flex flex-col items-center justify-center gap-5">
            <div className="h-44 w-44 shrink-0">
                <Doughnut data={data} options={options} />
            </div>
            <ul className="w-full max-w-64 space-y-2">
                {breakdown.map((slice, i) => {
                    const percent = Math.round((slice.revenue_minor / total) * 100);
                    return (
                        <li key={slice.stream} className="flex items-center gap-2 text-sm">
                            <span
                                className="h-2.5 w-2.5 rounded-full shrink-0"
                                style={{ backgroundColor: colors[i] }}
                                aria-hidden="true"
                            />
                            <span className="text-admin-text font-medium truncate">{slice.label}</span>
                            <span className="text-admin-text3 ml-auto shrink-0">{percent}%</span>
                            <span className="text-admin-text2 font-mono shrink-0 w-20 text-right">{formatNaira(slice.revenue_minor)}</span>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
