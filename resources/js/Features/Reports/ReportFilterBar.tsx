import { router } from '@inertiajs/react';
import AdminSelect from '../../Components/Admin/Forms/AdminSelect';
import { ReportFilters, ShopOption } from './types';

const RANGES = [
    ['today', 'Today'],
    ['7_days', '7 days'],
    ['30_days', '30 days'],
    ['this_month', 'This month'],
    ['last_month', 'Last month'],
    ['custom', 'Custom'],
];

export default function ReportFilterBar({ filters, shops }: { filters: ReportFilters; shops: ShopOption[] }) {
    function update(next: Partial<ReportFilters>) {
        const merged = { ...filters, ...next };
        router.get(
            window.location.pathname,
            {
                range: merged.range,
                start_date: merged.range === 'custom' ? merged.start_date : undefined,
                end_date: merged.range === 'custom' ? merged.end_date : undefined,
                shop_id: merged.shop_id ?? '',
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-3 mb-4">
            <div className="flex flex-wrap items-center gap-2">
                <AdminSelect aria-label="Date range" value={filters.range} onChange={(event) => update({ range: event.target.value })} containerClassName="mb-0">
                    {RANGES.map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
                    ))}
                </AdminSelect>

                {filters.range === 'custom' && (
                    <>
                        <input
                            type="date"
                            value={filters.start_date}
                            onChange={(event) => update({ start_date: event.target.value })}
                            className="border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg"
                        />
                        <input
                            type="date"
                            value={filters.end_date}
                            onChange={(event) => update({ end_date: event.target.value })}
                            className="border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg"
                        />
                    </>
                )}

                <AdminSelect aria-label="Filter by shop" value={filters.shop_id ?? ''} onChange={(event) => update({ shop_id: event.target.value ? Number(event.target.value) : null })} containerClassName="mb-0">
                    <option value="">All accessible shops</option>
                    {shops.map((shop) => (
                        <option key={shop.id} value={shop.id}>{shop.name}</option>
                    ))}
                </AdminSelect>
            </div>
        </div>
    );
}

