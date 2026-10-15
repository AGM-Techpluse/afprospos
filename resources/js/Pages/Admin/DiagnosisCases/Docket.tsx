import { Head } from '@inertiajs/react';
import AdminButton from '../../../Components/Admin/AdminButton';
import { formatNaira } from '../../../lib/money';

type RevealedFault = { id: number; label: string; notes: string | null };
type DiagnosisCase = {
    id: number;
    customer_name: string | null;
    customer_phone: string | null;
    device_make: string;
    device_model: string;
    device_imei_serial: string | null;
    reported_issue: string | null;
    outcome: string | null;
    diagnosis_notes: string | null;
    diagnosis_fee_minor: number | null;
    revealed_faults: RevealedFault[];
    created_at: string;
};

interface Props {
    diagnosisCase: DiagnosisCase;
    shop: { name: string } | null;
}

export default function DiagnosisCaseDocket({ diagnosisCase, shop }: Props) {
    return (
        <main className="min-h-screen bg-admin-bg p-6">
            <Head title={`Diagnosis docket #${diagnosisCase.id}`} />
            <div className="print:hidden max-w-[520px] mx-auto mb-4 flex justify-between">
                <AdminButton variant="neutral" onClick={() => history.back()}>Back</AdminButton>
                <AdminButton onClick={() => window.print()}>Print</AdminButton>
            </div>

            <section className="print-area max-w-[520px] mx-auto bg-white border border-admin-border rounded-admin-card p-6 text-admin-text">
                <div className="flex items-start justify-between gap-4 border-b border-admin-border pb-4 mb-4">
                    <div>
                        <h1 className="text-xl font-bold">Diagnosis docket</h1>
                        <p className="text-sm text-admin-text2">Case #{diagnosisCase.id}</p>
                    </div>
                    <div className="text-right text-sm">
                        <div className="font-semibold">{shop?.name ?? 'Shop'}</div>
                        <div>{new Date(diagnosisCase.created_at).toLocaleString()}</div>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 text-sm mb-4">
                    <div>
                        <div className="text-admin-text2">Customer</div>
                        <div className="font-semibold">{diagnosisCase.customer_name ?? 'Unknown customer'}</div>
                        {diagnosisCase.customer_phone && <div>{diagnosisCase.customer_phone}</div>}
                    </div>
                    <div>
                        <div className="text-admin-text2">Device</div>
                        <div className="font-semibold">{diagnosisCase.device_make} {diagnosisCase.device_model}</div>
                        <div>{diagnosisCase.device_imei_serial ?? 'No IMEI / serial'}</div>
                    </div>
                </div>

                <div className="border-t border-admin-border py-3">
                    <div className="text-sm text-admin-text2">Reported issue</div>
                    <p className="text-sm whitespace-pre-wrap">{diagnosisCase.reported_issue ?? 'Not recorded'}</p>
                </div>

                <div className="border-t border-admin-border py-3">
                    <div className="text-sm text-admin-text2 mb-1">Revealed faults</div>
                    {diagnosisCase.revealed_faults.length === 0 ? (
                        <p className="text-sm">No revealed faults recorded.</p>
                    ) : (
                        <ul className="list-disc pl-5 text-sm">
                            {diagnosisCase.revealed_faults.map((fault) => (
                                <li key={fault.id}>
                                    <span className="font-semibold">{fault.label}</span>
                                    {fault.notes && <span> - {fault.notes}</span>}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="border-t border-admin-border py-3 text-sm">
                    <div className="flex justify-between"><span>Outcome</span><span className="font-semibold">{diagnosisCase.outcome?.replace(/_/g, ' ') ?? 'Pending'}</span></div>
                    <div className="flex justify-between"><span>Diagnosis fee</span><span className="font-semibold">{diagnosisCase.diagnosis_fee_minor !== null ? formatNaira(diagnosisCase.diagnosis_fee_minor) : 'Not set'}</span></div>
                </div>

                {diagnosisCase.diagnosis_notes && (
                    <div className="border-t border-admin-border py-3">
                        <div className="text-sm text-admin-text2">Notes</div>
                        <p className="text-sm whitespace-pre-wrap">{diagnosisCase.diagnosis_notes}</p>
                    </div>
                )}
            </section>
        </main>
    );
}
