import { useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminBadge from '../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';

type Shop = { id: number; name: string; is_warehouse?: boolean };
type DiagnosisCase = {
    id: number;
    shop_id: number;
    customer_name: string | null;
    walk_in_name: string | null;
    device: string;
    device_imei_serial: string | null;
    reported_issue: string | null;
    faults: string[];
};

interface RepairCreateProps {
    shops: Shop[];
    diagnosisCases: DiagnosisCase[];
    filters: { shop_id: number | null };
}

export default function RepairCreate({ shops, diagnosisCases, filters }: RepairCreateProps) {
    const [term, setTerm] = useState('');
    const [creatingId, setCreatingId] = useState<number | null>(null);

    const filteredCases = useMemo(() => {
        const query = term.trim().toLowerCase();
        if (query === '') return diagnosisCases;

        return diagnosisCases.filter((diagnosisCase) =>
            [
                diagnosisCase.device,
                diagnosisCase.device_imei_serial ?? '',
                diagnosisCase.customer_name ?? diagnosisCase.walk_in_name ?? '',
                diagnosisCase.reported_issue ?? '',
                diagnosisCase.faults.join(' '),
            ].some((value) => value.toLowerCase().includes(query)),
        );
    }, [diagnosisCases, term]);

    function setShop(shopId: string) {
        router.get('/admin/repairs/create', { shop_id: shopId || undefined }, { preserveScroll: true, preserveState: true });
    }

    function createRepair(diagnosisCaseId: number) {
        setCreatingId(diagnosisCaseId);
        router.post(
            `/admin/repairs/diagnosis-cases/${diagnosisCaseId}/repairs`,
            {},
            { onFinish: () => setCreatingId(null) },
        );
    }

    return (
        <AdminShell>
            <Head title="New repair" />

            <AdminPageHead
                title="New repair"
                description="Select a saved repairable diagnosis and call it up as a repair work order."
                actions={<AdminButton onClick={() => router.visit('/admin/repairs/diagnosis-cases/create')}>New diagnosis</AdminButton>}
            />

            <div className="mb-4 grid gap-2 sm:grid-cols-[220px_minmax(0,1fr)] max-w-5xl">
                <select
                    value={filters.shop_id ?? ''}
                    onChange={(event) => setShop(event.target.value)}
                    className="border border-admin-border-strong rounded-admin-button px-3 py-2 text-sm bg-white"
                >
                    <option value="">All shops</option>
                    {shops.filter((shop) => !shop.is_warehouse).map((shop) => (
                        <option key={shop.id} value={shop.id}>
                            {shop.name}
                        </option>
                    ))}
                </select>
                <input
                    value={term}
                    onChange={(event) => setTerm(event.target.value)}
                    placeholder="Search by device, IMEI, customer, issue, or fault..."
                    className="border border-admin-border-strong rounded-admin-button px-3 py-2 text-sm outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                />
            </div>

            <div className="bg-admin-surface border border-admin-border rounded-admin-card overflow-hidden max-w-5xl">
                <div className="hidden md:grid grid-cols-[1.1fr_1fr_1.2fr_130px] gap-3 px-4 py-3 text-xs font-semibold text-admin-text2 border-b border-admin-border">
                    <span>Device</span>
                    <span>Customer</span>
                    <span>Revealed faults</span>
                    <span></span>
                </div>

                {filteredCases.length === 0 ? (
                    <AdminEmptyState
                        icon="tools"
                        title="No repairable diagnoses found"
                        description="Create and save a diagnosis first, then call it up here for repair."
                    />
                ) : (
                    filteredCases.map((diagnosisCase) => (
                        <div
                            key={diagnosisCase.id}
                            className="grid gap-3 md:grid-cols-[1.1fr_1fr_1.2fr_130px] md:items-center px-4 py-3 border-b border-admin-border last:border-b-0"
                        >
                            <div className="min-w-0">
                                <div className="text-sm font-semibold text-admin-text truncate">{diagnosisCase.device}</div>
                                <div className="text-xs text-admin-text3">{diagnosisCase.device_imei_serial ?? 'No IMEI / serial'}</div>
                                {diagnosisCase.reported_issue && <div className="text-xs text-admin-text2 mt-1 truncate">{diagnosisCase.reported_issue}</div>}
                            </div>
                            <div className="text-sm text-admin-text">{diagnosisCase.customer_name ?? diagnosisCase.walk_in_name ?? 'Unknown customer'}</div>
                            <div className="flex flex-wrap gap-1">
                                {diagnosisCase.faults.slice(0, 4).map((fault) => (
                                    <AdminBadge key={fault} status="neutral">{fault}</AdminBadge>
                                ))}
                                {diagnosisCase.faults.length > 4 && <span className="text-xs text-admin-text3">+{diagnosisCase.faults.length - 4}</span>}
                            </div>
                            <AdminButton onClick={() => createRepair(diagnosisCase.id)} isLoading={creatingId === diagnosisCase.id}>
                                Create repair
                            </AdminButton>
                        </div>
                    ))
                )}
            </div>
        </AdminShell>
    );
}
