import { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import AdminSelect from '../../../Components/Admin/Forms/AdminSelect';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type CommissionEntry = {
    id: number;
    staff_id: number;
    staff_name: string;
    role_snapshot: string;
    source_type: 'sale' | 'repair';
    source_id: number;
    commissionable_amount_minor: number;
    entry_type: string;
    status: string;
    shop_id: number;
    created_at: string;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };
type Shop = { id: number; name: string };
type StaffOption = { id: number; name: string };

interface CommissionIndexProps {
    entries: Paginated<CommissionEntry>;
    filters: { staff_id: number | null; shop_id: number | null; from_date: string | null; to_date: string | null };
    shops: Shop[];
    staffOptions: StaffOption[];
}

function sourceLink(entry: CommissionEntry): string {
    return entry.source_type === 'sale' ? `/admin/sales/${entry.source_id}` : `/admin/repairs/${entry.source_id}`;
}

const COLUMNS: DataTableColumn<CommissionEntry>[] = [
    { key: 'created_at', label: 'Date', render: (row) => new Date(row.created_at).toLocaleString() },
    { key: 'staff_name', label: 'Staff', render: (row) => `${row.staff_name} (${row.role_snapshot})` },
    {
        key: 'source_type',
        label: 'Source',
        render: (row) => (
            <Link href={sourceLink(row)} className="text-admin-blue hover:underline capitalize">
                {row.source_type} #{row.source_id}
            </Link>
        ),
    },
    { key: 'commissionable_amount_minor', label: 'Amount', render: (row) => formatNaira(row.commissionable_amount_minor) },
    { key: 'status', label: 'Status', render: (row) => <span className="capitalize">{row.status}</span> },
];

export default function CommissionIndex({ entries, filters, shops, staffOptions }: CommissionIndexProps) {
    const [staffId, setStaffId] = useState(filters.staff_id?.toString() ?? '');
    const [shopId, setShopId] = useState(filters.shop_id?.toString() ?? '');
    const [fromDate, setFromDate] = useState(filters.from_date ?? '');
    const [toDate, setToDate] = useState(filters.to_date ?? '');
    const skipNextFetch = useRef(true);

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/commission',
                {
                    staff_id: staffId || undefined,
                    shop_id: shopId || undefined,
                    from_date: fromDate || undefined,
                    to_date: toDate || undefined,
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [staffId, shopId, fromDate, toDate]);

    function goToPage(page: number) {
        router.get(
            '/admin/commission',
            { staff_id: staffId || undefined, shop_id: shopId || undefined, from_date: fromDate || undefined, to_date: toDate || undefined, page },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const filterInputClass =
        'border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus';

    return (
        <AdminShell>
            <Head title="Commission" />

            <AdminPageHead title="Commission" description="Commission earned by staff from fault-fix repairs and commissionable device sales." />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={entries.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="staff_name"
                mobileStatusField="status"
                mobileDetailFields={['source_type', 'commissionable_amount_minor', 'created_at']}
                toolbar={
                    <div className="flex flex-wrap items-center gap-2">
                        <AdminSelect aria-label="Filter by staff" value={staffId} onChange={(e) => setStaffId(e.target.value)} containerClassName="mb-0">
                            <option value="">All staff</option>
                            {staffOptions.map((option) => (
                                <option key={option.id} value={option.id}>{option.name}</option>
                            ))}
                        </AdminSelect>
                        {shops.length > 1 && (
                            <AdminSelect aria-label="Filter by shop" value={shopId} onChange={(e) => setShopId(e.target.value)} containerClassName="mb-0">
                                <option value="">All shops</option>
                                {shops.map((shop) => (
                                    <option key={shop.id} value={shop.id}>{shop.name}</option>
                                ))}
                            </AdminSelect>
                        )}
                        <input type="date" value={fromDate} onChange={(e) => setFromDate(e.target.value)} className={filterInputClass} />
                        <span className="text-xs text-admin-text3">to</span>
                        <input type="date" value={toDate} onChange={(e) => setToDate(e.target.value)} className={filterInputClass} />
                    </div>
                }
                emptyState={
                    <AdminEmptyState icon="cash-coin" title="No commission earned yet" description="Fault-fix repairs and commissionable device sales will show up here." />
                }
                footer={
                    entries.total > 0 ? (
                        <AdminPagination currentPage={entries.current_page} totalItems={entries.total} pageSize={entries.per_page} onPageChange={goToPage} />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
