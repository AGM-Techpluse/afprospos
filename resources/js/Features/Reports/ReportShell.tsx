import { Head, router } from '@inertiajs/react';
import { ReactNode } from 'react';
import AdminShell from '../../Components/Admin/AdminShell';
import AdminPageHead from '../../Components/Admin/AdminPageHead';
import AdminButton from '../../Components/Admin/AdminButton';
import Icon from '../../Components/Icons/Icon';
import ReportFilterBar from './ReportFilterBar';
import { ReportFilters, ShopOption } from './types';

export function ReportShell({
    title,
    description,
    filters,
    shops,
    children,
}: {
    title: string;
    description: string;
    filters: ReportFilters;
    shops: ShopOption[];
    children: ReactNode;
}) {
    return (
        <AdminShell>
            <Head title={title} />
            <AdminPageHead
                title={title}
                description={description}
                actions={
                    <AdminButton variant="neutral" onClick={() => router.visit('/admin/reports')}>
                        <Icon name="grid-3x3-gap" className="mr-1.5" />
                        Reports
                    </AdminButton>
                }
            />
            <ReportFilterBar filters={filters} shops={shops} />
            {children}
        </AdminShell>
    );
}

export function ReportPanel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="bg-admin-surface border border-admin-border rounded-admin-card p-5 min-w-0">
            <h2 className="text-sm font-semibold text-admin-text mb-4">{title}</h2>
            {children}
        </section>
    );
}
