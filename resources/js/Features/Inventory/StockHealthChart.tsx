import { ArcElement, Chart as ChartJS, Tooltip } from 'chart.js';
import { Doughnut } from 'react-chartjs-2';

ChartJS.register(ArcElement, Tooltip);

// Matches the Dashboard's existing status-color convention (danger=red, warning=yellow, success=green) — same hex values as tokens.css's --a-red/--a-yellow/--a-green, hardcoded like RevenueBreakdownChart's revenueStreamColors.ts since this card sits on the fixed dark admin-text background, not a theme-colored surface.
const WELL_STOCKED_COLOR = '#16A34A';
const LOW_STOCK_COLOR = '#F5A623';
const OUT_OF_STOCK_COLOR = '#D92D20';

export type StockHealthCounts = { total_skus: number; low_stock_count: number; out_of_stock_count: number };

/** Compact doughnut summarizing SKU stock health for the Dashboard's dark "Inventory health" card, total SKU count shown in the center. */
export default function StockHealthChart({ counts }: { counts: StockHealthCounts }) {
    const wellStocked = Math.max(counts.total_skus - counts.low_stock_count - counts.out_of_stock_count, 0);

    const data = {
        labels: ['Well stocked', 'Low stock', 'Out of stock'],
        datasets: [
            {
                data: [wellStocked, counts.low_stock_count, counts.out_of_stock_count],
                backgroundColor: [WELL_STOCKED_COLOR, LOW_STOCK_COLOR, OUT_OF_STOCK_COLOR],
                borderColor: '#101323',
                borderWidth: 2,
            },
        ],
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (context: { label: string; parsed: number }) =>
                        `${context.label}: ${context.parsed} (${counts.total_skus > 0 ? Math.round((context.parsed / counts.total_skus) * 100) : 0}%)`,
                },
            },
        },
    };

    const legend: { label: string; value: number; color: string }[] = [
        { label: 'Well stocked', value: wellStocked, color: WELL_STOCKED_COLOR },
        { label: 'Low stock', value: counts.low_stock_count, color: LOW_STOCK_COLOR },
        { label: 'Out of stock', value: counts.out_of_stock_count, color: OUT_OF_STOCK_COLOR },
    ];

    return (
        <div className="flex items-center gap-3">
            <div className="relative h-20 w-20 shrink-0">
                <Doughnut data={data} options={options} />
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-sm font-bold text-white leading-none">{counts.total_skus}</span>
                    <span className="text-[9px] text-white/60 leading-none mt-0.5">SKUs</span>
                </div>
            </div>
            <ul className="flex flex-col gap-1 min-w-0">
                {legend.map((item) => (
                    <li key={item.label} className="flex items-center gap-1.5 text-xs">
                        <span className="h-2 w-2 rounded-full shrink-0" style={{ backgroundColor: item.color }} aria-hidden="true" />
                        <span className="text-white/70 truncate">{item.label}</span>
                        <span className="text-white font-semibold ml-auto">{item.value}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
