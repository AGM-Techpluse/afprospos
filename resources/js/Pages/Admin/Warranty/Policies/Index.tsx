import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';

interface WarrantyPolicyRow {
    id: number;
    name: string;
    coverage_duration_days: number;
    coverage_start_point: string;
    coverage_extent: string;
}

interface WarrantyPoliciesIndexProps {
    policies: WarrantyPolicyRow[];
}

const COLUMNS: DataTableColumn<WarrantyPolicyRow>[] = [
    { key: 'name', label: 'Name' },
    { key: 'coverage_duration_days', label: 'Duration (days)' },
    { key: 'coverage_start_point', label: 'Starts from' },
    { key: 'coverage_extent', label: 'Extent' },
];

export default function WarrantyPoliciesIndex({ policies }: WarrantyPoliciesIndexProps) {
    return (
        <AdminShell>
            <Head title="Warranty policies" />

            <AdminPageHead
                title="Warranty policies"
                description="Configurable coverage duration, exclusions, and remedies (BLD WAR-BR-01)."
                actions={<AdminButton onClick={() => router.visit('/admin/warranty/policies/create')}>New policy</AdminButton>}
            />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={policies}
                getRowKey={(row) => row.id}
                mobilePrimaryField="name"
                mobileDetailFields={['coverage_duration_days', 'coverage_extent']}
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/warranty/policies/${row.id}/edit`)}>
                        Edit
                    </AdminButton>
                )}
                emptyState={
                    <AdminEmptyState
                        icon="shield-check"
                        title="No warranty policies yet"
                        description="Create a policy to start accepting warranty claims against it."
                    />
                }
            />
        </AdminShell>
    );
}
