import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';

type ImportRowResult = {
    row_number: number;
    succeeded: boolean;
    sku_code: string | null;
    error_message: string | null;
};

export default function ImportResults({ results }: { results: ImportRowResult[] }) {
    const succeeded = results.filter((r) => r.succeeded).length;
    const failed = results.length - succeeded;

    return (
        <AdminShell>
            <Head title="Import results" />

            <AdminPageHead
                title="Import results"
                description={`${succeeded} row(s) succeeded, ${failed} failed — one bad row never affects the rest of the batch.`}
                backHref="/admin/inventory/import"
                backLabel="Back to import"
                actions={<AdminButton onClick={() => router.visit('/admin/inventory/products')}>View products</AdminButton>}
            />

            <div className="bg-admin-surface border border-admin-border rounded-admin-card overflow-hidden">
                <table className="w-full">
                    <thead>
                        <tr className="bg-admin-table-head">
                            <th className="text-left text-admin-xs font-semibold text-admin-text2 px-admin-table-cell-x py-admin-table-cell-y">Row</th>
                            <th className="text-left text-admin-xs font-semibold text-admin-text2 px-admin-table-cell-x py-admin-table-cell-y">Status</th>
                            <th className="text-left text-admin-xs font-semibold text-admin-text2 px-admin-table-cell-x py-admin-table-cell-y">Result</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-admin-border">
                        {results.map((row) => (
                            <tr key={row.row_number}>
                                <td className="text-admin-table-cell text-admin-text px-admin-table-cell-x py-admin-table-cell-y">{row.row_number}</td>
                                <td className="px-admin-table-cell-x py-admin-table-cell-y">
                                    <AdminBadge status={row.succeeded ? 'success' : 'danger'}>{row.succeeded ? 'Imported' : 'Failed'}</AdminBadge>
                                </td>
                                <td className="text-admin-table-cell px-admin-table-cell-x py-admin-table-cell-y">
                                    {row.succeeded ? (
                                        <span className="text-admin-text">{row.sku_code}</span>
                                    ) : (
                                        <span className="text-admin-red">{row.error_message}</span>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AdminShell>
    );
}
