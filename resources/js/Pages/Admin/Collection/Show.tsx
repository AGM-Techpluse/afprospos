import { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useConfirm } from '../../../hooks/useConfirm';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminBadge, { AdminBadgeStatus } from '../../../Components/Admin/AdminBadge';
import { formatNaira } from '../../../lib/money';

type CaseEvent = {
    id: number;
    event_type: string;
    actor_staff_id: number | null;
    detail: Record<string, unknown>;
    created_at: string;
};

type CollectionCaseDetail = {
    id: number;
    source_type: string;
    source_id: number;
    context: string;
    status: string;
    collection_deadline_at: string;
    abandonment_threshold_at: string | null;
    accrued_storage_fee_minor: number;
    shop_override_reason: string | null;
    events: CaseEvent[];
};

interface CollectionShowProps {
    collectionCase: CollectionCaseDetail;
}

const STATUS_BADGE: Record<string, AdminBadgeStatus> = {
    pending: 'neutral',
    overdue: 'warning',
    abandoned: 'danger',
    resolved: 'success',
};

export default function CollectionShow({ collectionCase: item }: CollectionShowProps) {
    const confirm = useConfirm();
    const [processing, setProcessing] = useState(false);
    const [note, setNote] = useState('');
    const [overrideReason, setOverrideReason] = useState('');
    const hasOverride = item.events.some((event) => event.event_type === 'administrative_resolution');

    function post(url: string, data: Record<string, string> = {}) {
        setProcessing(true);
        router.post(url, data, { onFinish: () => setProcessing(false) });
    }

    function notify(e: FormEvent) {
        e.preventDefault();
        post(`/admin/collection/${item.id}/notify`, { note });
    }

    function recordOverride(e: FormEvent) {
        e.preventDefault();
        post(`/admin/collection/${item.id}/override`, { reason: overrideReason });
    }

    async function release() {
        const confirmed = await confirm({
            kind: 'warning',
            title: 'Release this device?',
            body: 'This marks the collection case resolved. It cannot be reversed from here.',
            confirmLabel: 'Release device',
        });
        if (confirmed) post(`/admin/collection/${item.id}/release`);
    }

    return (
        <AdminShell>
            <Head title={`Collection case #${item.id}`} />

            <AdminPageHead
                title={`Collection case #${item.id}`}
                description={`${item.source_type.replace('_', ' ')} #${item.source_id} — ${item.context.replace(/_/g, ' ')}`}
                actions={<AdminBadge status={STATUS_BADGE[item.status] ?? 'neutral'}>{item.status}</AdminBadge>}
            />

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] items-start max-w-4xl">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    <h2 className="text-sm font-semibold text-admin-text mb-3">Event history</h2>
                    {item.events.length === 0 ? (
                        <p className="text-sm text-admin-text3">No events yet.</p>
                    ) : (
                        <ul className="flex flex-col gap-3">
                            {item.events.map((event) => (
                                <li key={event.id} className="border-l-2 border-admin-border pl-3">
                                    <div className="text-sm font-medium text-admin-text capitalize">{event.event_type.replace(/_/g, ' ')}</div>
                                    <div className="text-xs text-admin-text3">{new Date(event.created_at).toLocaleString()}</div>
                                    {Object.keys(event.detail).length > 0 && (
                                        <div className="text-xs text-admin-text2 mt-1">{JSON.stringify(event.detail)}</div>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6 flex flex-col gap-2">
                    <h2 className="text-sm font-semibold text-admin-text mb-1">Actions</h2>

                    <div className="text-xs text-admin-text2 mb-2">
                        Deadline: {new Date(item.collection_deadline_at).toLocaleString()}
                        <br />
                        Accrued storage fee: {formatNaira(item.accrued_storage_fee_minor)}
                    </div>

                    {item.status !== 'resolved' && (
                        <>
                            <form onSubmit={notify} className="flex flex-col gap-2">
                                <label className="text-xs font-semibold text-admin-text">Mark customer notified</label>
                                <textarea
                                    value={note}
                                    onChange={(e) => setNote(e.target.value)}
                                    rows={2}
                                    placeholder="Note (optional)"
                                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                />
                                <AdminButton type="submit" variant="neutral" isLoading={processing}>
                                    Record notification
                                </AdminButton>
                            </form>

                            <AdminButton onClick={release} disabled={processing} className="mt-2">
                                Release device
                            </AdminButton>

                            {!hasOverride && (
                                <form onSubmit={recordOverride} className="flex flex-col gap-2 border-t border-admin-border pt-3 mt-1">
                                    <label className="text-xs font-semibold text-admin-text">Record administrative override</label>
                                    <textarea
                                        value={overrideReason}
                                        onChange={(e) => setOverrideReason(e.target.value)}
                                        rows={2}
                                        placeholder="Reason for releasing despite an outstanding balance"
                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                    />
                                    <AdminButton type="submit" variant="neutral" isLoading={processing}>
                                        Record override
                                    </AdminButton>
                                </form>
                            )}
                        </>
                    )}

                    {item.status === 'resolved' && <p className="text-xs text-admin-text3">This case is resolved — no further actions available.</p>}
                </div>
            </div>
        </AdminShell>
    );
}
