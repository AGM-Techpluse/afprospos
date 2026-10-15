import { formatNaira } from '../../lib/money';

export default function ReportMetricCard({ label, value, valueMinor }: { label: string; value?: number | string; valueMinor?: number }) {
    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4 min-w-0">
            <div className="text-sm text-admin-text2 mb-1 truncate">{label}</div>
            <div className="text-admin-lg font-bold text-admin-text truncate">
                {valueMinor !== undefined ? formatNaira(valueMinor) : value}
            </div>
        </div>
    );
}

