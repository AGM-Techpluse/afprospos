import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import Icon from '../../../Components/Icons/Icon';
import { DoughnutBreakdown } from '../../../Features/Reports/ReportCharts';
import ReportFilterBar from '../../../Features/Reports/ReportFilterBar';
import { BreakdownPoint, Metric, ReportFilters, ShopOption } from '../../../Features/Reports/types';
import { formatNaira } from '../../../lib/money';

type Card = { href: string; metrics: Metric[]; preview: BreakdownPoint[] };

const MODULES: Record<string, { title: string; description: string; icon: string }> = {
    repairs: { title: 'Repair reports', description: 'Lifecycle, outcomes, technician work, collections, refunds, and service charges.', icon: 'tools' },
    sales: { title: 'Sales report', description: 'Revenue, invoices, cashier performance, payment methods, and top products.', icon: 'receipt' },
    inventory: { title: 'Inventory reports', description: 'Stock value, availability, low stock, serialized units, and transfers.', icon: 'box-seam' },
    staff: { title: 'Staff report', description: 'Role mix, shop coverage, cashier sales, technician output, and activity.', icon: 'people' },
};

export default function ReportsIndex({ cards, filters, shops }: { cards: Record<string, Card>; filters: ReportFilters; shops: ShopOption[] }) {
    return (
        <AdminShell>
            <Head title="Reports" />
            <AdminPageHead title="Reports" description="Operational reporting across repairs, sales, inventory, and staff." />
            <ReportFilterBar filters={filters} shops={shops} />

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {Object.entries(MODULES).map(([key, module]) => {
                    const card = cards[key];

                    return (
                        <button
                            key={key}
                            type="button"
                            onClick={() => router.visit(card.href)}
                            className="bg-admin-surface border border-admin-border rounded-admin-card p-5 text-left hover:border-admin-blue transition-colors min-w-0"
                        >
                            <div className="flex items-start gap-3 mb-4">
                                <div className="w-10 h-10 rounded-full bg-admin-blue-soft text-admin-blue flex items-center justify-center shrink-0">
                                    <Icon name={module.icon} />
                                </div>
                                <div className="min-w-0">
                                    <h2 className="font-semibold text-admin-text">{module.title}</h2>
                                    <p className="text-sm text-admin-text2 mt-1">{module.description}</p>
                                </div>
                            </div>
                            <div className="grid grid-cols-3 gap-2 mb-4">
                                {card.metrics.map((metric) => (
                                    <div key={metric.label} className="min-w-0">
                                        <div className="text-xs text-admin-text3 truncate">{metric.label}</div>
                                        <div className="text-sm font-bold text-admin-text truncate">
                                            {metric.value_minor !== undefined ? formatNaira(metric.value_minor) : metric.value}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div className="border-t border-admin-border pt-3">
                                <DoughnutBreakdown rows={card.preview.slice(0, 5)} money={key === 'sales' && card.preview.some((row) => row.value_minor !== undefined)} />
                            </div>
                        </button>
                    );
                })}
            </div>
        </AdminShell>
    );
}

