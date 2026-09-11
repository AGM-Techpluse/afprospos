interface AdminStatCardProps {
    label: string;
    value: string;
    /** Signed delta text, e.g. "+12%" / "-3%" — color follows sign (UI/UX §3.4: green = positive delta). */
    delta?: string;
    deltaDirection?: 'up' | 'down';
}

/** UI/UX §6.1 — flat, border-driven, no shadow (§14B.8's dashboard grid: spans 3 columns desktop). */
export default function AdminStatCard({ label, value, delta, deltaDirection }: AdminStatCardProps) {
    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4">
            <div className="text-admin-text2 text-xs font-semibold uppercase tracking-wide">{label}</div>
            <div className="text-admin-xl font-bold text-admin-text mt-1">{value}</div>
            {delta && (
                <div className={`text-xs font-semibold mt-1 ${deltaDirection === 'down' ? 'text-admin-red' : 'text-admin-green'}`}>
                    {delta}
                </div>
            )}
        </div>
    );
}
