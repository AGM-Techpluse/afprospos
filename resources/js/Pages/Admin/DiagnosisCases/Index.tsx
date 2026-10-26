import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminBadge, { AdminBadgeStatus } from '../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import AdminSelect from '../../../Components/Admin/Forms/AdminSelect';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';

type Shop = { id: number; name: string };
type DiagnosisCase = {
    id: number;
    customer_name: string | null;
    walk_in_name: string | null;
    device: string;
    device_imei_serial: string | null;
    status: string;
    outcome: string | null;
    open_repair_id: number | null;
    open_repair_status: string | null;
    faults: string[];
    created_at: string;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

interface Props {
    cases: Paginated<DiagnosisCase>;
    filters: { search: string; status: string; outcome: string; shop_id: number | null };
    shops: Shop[];
}

const STATUS_BADGE: Record<string, AdminBadgeStatus> = {
    received: 'neutral',
    diagnosed: 'info',
    called_up: 'success',
    declined: 'warning',
};

const OUTCOME_LABELS: Record<string, string> = {
    repairable: 'Repairable',
    unrepairable: 'Unrepairable',
    requires_further_assessment: 'Further assessment',
};

const NO_FILTERS = { search: '', status: '', outcome: '' };

export default function DiagnosisCasesIndex({ cases, filters, shops }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [outcome, setOutcome] = useState(filters.outcome);
    const [shopId, setShopId] = useState(filters.shop_id);
    const [refreshing, setRefreshing] = useState(false);
    const skipNextFetch = useRef(true);

    const hasActiveFilters = Boolean(filters.search || filters.status || filters.outcome || filters.shop_id);

    const columns: DataTableColumn<DiagnosisCase>[] = [
        {
            key: 'device',
            label: 'Device',
            render: (row) => (
                <div>
                    <div className="font-semibold text-admin-text">{row.device}</div>
                    <div className="text-admin-xs text-admin-text3">{row.device_imei_serial ?? 'No IMEI / serial'}</div>
                </div>
            ),
        },
        { key: 'customer_name', label: 'Customer', render: (row) => row.customer_name ?? row.walk_in_name ?? 'Unknown customer' },
        {
            key: 'status',
            label: 'Status',
            render: (row) => <AdminBadge status={STATUS_BADGE[row.status] ?? 'neutral'}>{row.status.replace(/_/g, ' ')}</AdminBadge>,
        },
        { key: 'outcome', label: 'Outcome', render: (row) => row.outcome ? OUTCOME_LABELS[row.outcome] ?? row.outcome.replace(/_/g, ' ') : 'Not saved' },
        {
            key: 'faults',
            label: 'Revealed faults',
            render: (row) => (
                <div className="flex flex-wrap gap-1">
                    {row.faults.length === 0 ? (
                        <span className="text-admin-text3">None saved</span>
                    ) : (
                        <>
                            {row.faults.slice(0, 3).map((fault) => (
                                <span key={fault} className="bg-admin-hover text-admin-text2 rounded-admin-badge px-2 py-1 text-xs">
                                    {fault}
                                </span>
                            ))}
                            {row.faults.length > 3 && <span className="text-xs text-admin-text3">+{row.faults.length - 3}</span>}
                        </>
                    )}
                </div>
            ),
        },
        { key: 'created_at', label: 'Received', render: (row) => new Date(row.created_at).toLocaleDateString() },
    ];

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/repairs/diagnosis-cases',
                {
                    search: search || undefined,
                    status: status || undefined,
                    outcome: outcome || undefined,
                    shop_id: shopId ?? '',
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status, outcome, shopId]);

    function goToPage(page: number) {
        router.get(
            '/admin/repairs/diagnosis-cases',
            {
                search: search || undefined,
                status: status || undefined,
                outcome: outcome || undefined,
                shop_id: shopId ?? '',
                page,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function refresh() {
        router.reload({
            only: ['cases'],
            onStart: () => setRefreshing(true),
            onFinish: () => setRefreshing(false),
        });
    }

    function clearFilters() {
        skipNextFetch.current = true;
        setSearch(NO_FILTERS.search);
        setStatus(NO_FILTERS.status);
        setOutcome(NO_FILTERS.outcome);
        setShopId(null);
        router.get('/admin/repairs/diagnosis-cases', {}, { preserveState: true, preserveScroll: true, replace: true });
    }

    function createRepair(row: DiagnosisCase) {
        router.post(`/admin/repairs/diagnosis-cases/${row.id}/repairs`, {});
    }

    function repairAction(row: DiagnosisCase) {
        if (row.open_repair_id !== null) {
            return (
                <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/${row.open_repair_id}`)}>
                    Open repair work
                </AdminButton>
            );
        }

        if (row.status === 'diagnosed' && row.outcome === 'repairable') {
            return (
                <AdminButton variant="neutral" onClick={() => createRepair(row)}>
                    Create repair
                </AdminButton>
            );
        }

        return null;
    }

    return (
        <AdminShell>
            <Head title="Diagnosis cases" />
            <AdminPageHead
                title="Diagnosis cases"
                description="Saved device diagnoses that can be called up for repair later."
                actions={
                    <div className="flex items-center gap-2">
                        <AdminButton variant="neutral" onClick={() => router.visit('/admin/repairs')}>Repair jobs</AdminButton>
                        <AdminButton onClick={() => router.visit('/admin/repairs/diagnosis-cases/create')}>New diagnosis</AdminButton>
                    </div>
                }
            />

            <ResponsiveDataTable
                columns={columns}
                rows={cases.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="device"
                mobileStatusField="status"
                mobileDetailFields={['customer_name', 'outcome', 'faults', 'created_at']}
                loading={refreshing}
                toolbar={
                    <>
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search device, customer, fault..."
                            className="flex-1 min-w-[180px] max-w-[280px] border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        />
                        <AdminSelect aria-label="Filter by status" value={status} onChange={(e) => setStatus(e.target.value)} containerClassName="mb-0">
                            <option value="">All statuses</option>
                            <option value="received">Received</option>
                            <option value="diagnosed">Diagnosed</option>
                            <option value="called_up">Called up</option>
                            <option value="declined">Declined</option>
                        </AdminSelect>
                        <AdminSelect aria-label="Filter by outcome" value={outcome} onChange={(e) => setOutcome(e.target.value)} containerClassName="mb-0">
                            <option value="">All outcomes</option>
                            <option value="repairable">Repairable</option>
                            <option value="unrepairable">Unrepairable</option>
                            <option value="requires_further_assessment">Further assessment</option>
                        </AdminSelect>
                        <AdminSelect aria-label="Filter by shop" value={shopId ?? ''} onChange={(e) => setShopId(e.target.value ? Number(e.target.value) : null)} containerClassName="mb-0">
                            <option value="">All shops</option>
                            {shops.map((shop) => (
                                <option key={shop.id} value={shop.id}>{shop.name}</option>
                            ))}
                        </AdminSelect>
                        <div className="flex-1" />
                        <AdminButton type="button" variant="neutral" onClick={refresh} isLoading={refreshing} loadingText="Refreshing...">
                            Refresh
                        </AdminButton>
                        {hasActiveFilters && (
                            <AdminButton type="button" variant="warning" onClick={clearFilters}>
                                Clear filters
                            </AdminButton>
                        )}
                    </>
                }
                renderActions={(row) => (
                    <div className="flex flex-wrap items-center justify-end gap-2">
                        {repairAction(row)}
                        <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/diagnosis-cases/${row.id}`)}>
                            Open diagnosis
                        </AdminButton>
                    </div>
                )}
                emptyState={
                    <AdminEmptyState
                        icon="clipboard-data"
                        title="No diagnosis cases found"
                        description={hasActiveFilters ? 'No diagnosis cases match these filters.' : 'Saved diagnoses will show up here.'}
                    />
                }
                footer={
                    cases.total > 0 ? (
                        <AdminPagination
                            currentPage={cases.current_page}
                            totalItems={cases.total}
                            pageSize={cases.per_page}
                            onPageChange={goToPage}
                        />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
