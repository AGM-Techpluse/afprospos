import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../../Components/Admin/AdminPagination';
import AdminStatCard from '../../../../Components/Admin/AdminStatCard';
import AdminModal from '../../../../Components/Admin/AdminModal';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../../lib/money';

type ExpenseRow = {
    id: number;
    shop_id: number | null;
    shop_name: string | null;
    category: string;
    amount_minor: number;
    note: string | null;
    recorded_by_staff_name: string | null;
    created_at: string;
};

type Shop = { id: number; name: string };
type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface ExpensesIndexProps {
    expenses: Paginated<ExpenseRow>;
    totalMinor: number;
    filters: { shop_id: number | null; category: string };
    shops: Shop[];
    categories: Record<string, string>;
}

function EditExpenseModal({ expense, categories, onClose }: { expense: ExpenseRow; categories: Record<string, string>; onClose: () => void }) {
    const { data, setData, put, transform, processing, errors: formErrors } = useForm({
        category: expense.category,
        amount_naira: String(expense.amount_minor / 100),
        note: expense.note ?? '',
    });
    // The server validates the minor-unit field (`amount_minor`) that doesn't
    // match this form's naira-bound field name.
    const errors = formErrors as Record<string, string | undefined>;

    function submit(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            category: formData.category,
            amount_minor: Math.round(parseFloat(formData.amount_naira || '0') * 100),
            note: formData.note || null,
        }));

        put(`/admin/finance/expenses/${expense.id}`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    }

    return (
        <AdminModal open title="Edit expense" onClose={onClose}>
            <form id="edit-expense-form" onSubmit={submit} className="pb-6 flex flex-col gap-4">
                <AdminSelect label="Category" value={data.category} onChange={(e) => setData('category', e.target.value)} error={errors.category}>
                    {Object.entries(categories).map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </AdminSelect>
                <div>
                    <label className="block text-xs font-semibold text-admin-text mb-1.5">Amount (₦)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={data.amount_naira}
                        onChange={(e) => setData('amount_naira', e.target.value)}
                        className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                    />
                    {errors.amount_minor && <p className="text-xs text-admin-red mt-1">{errors.amount_minor}</p>}
                </div>
                <div>
                    <label className="block text-xs font-semibold text-admin-text mb-1.5">Note (optional)</label>
                    <textarea
                        value={data.note}
                        onChange={(e) => setData('note', e.target.value)}
                        rows={3}
                        className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                    />
                </div>
                <AdminButton type="submit" isLoading={processing}>
                    Save changes
                </AdminButton>
            </form>
        </AdminModal>
    );
}

export default function ExpensesIndex({ expenses, totalMinor, filters, shops, categories }: ExpensesIndexProps) {
    const [editing, setEditing] = useState<ExpenseRow | null>(null);

    function applyFilters(next: Partial<{ shop_id: number | null; category: string }>) {
        router.get(
            '/admin/finance/expenses',
            {
                shop_id: next.shop_id !== undefined ? (next.shop_id ?? '') : (filters.shop_id ?? ''),
                category: next.category ?? filters.category,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function goToPage(page: number) {
        router.get(
            '/admin/finance/expenses',
            { shop_id: filters.shop_id ?? '', category: filters.category || undefined, page },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const columns: DataTableColumn<ExpenseRow>[] = [
        { key: 'created_at', label: 'Date', render: (row) => new Date(row.created_at).toLocaleDateString() },
        { key: 'shop_name', label: 'Shop', render: (row) => row.shop_name ?? <span className="text-admin-text3">Business-wide</span> },
        { key: 'category', label: 'Category', render: (row) => categories[row.category] ?? row.category },
        { key: 'amount_minor', label: 'Amount', render: (row) => formatNaira(row.amount_minor) },
        { key: 'recorded_by_staff_name', label: 'Recorded by', render: (row) => row.recorded_by_staff_name ?? '—' },
    ];

    return (
        <AdminShell>
            <Head title="Expenses" />

            <AdminPageHead
                title="Expenses"
                description="Business costs — salaries, rent, utilities, and other one-off expenses."
                actions={<AdminButton onClick={() => router.visit('/admin/finance/expenses/create')}>Record expense</AdminButton>}
            />

            <div className="mb-6 max-w-xs">
                <AdminStatCard label="Total (this filter)" value={formatNaira(totalMinor)} variant="blue" fillHeight />
            </div>

            <ResponsiveDataTable
                columns={columns}
                rows={expenses.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="category"
                mobileStatusField="amount_minor"
                mobileDetailFields={['shop_name', 'recorded_by_staff_name', 'created_at']}
                toolbar={
                    <>
                        <AdminSelect
                            aria-label="Filter by shop"
                            value={filters.shop_id ?? ''}
                            onChange={(e) => applyFilters({ shop_id: e.target.value ? Number(e.target.value) : null })}
                            containerClassName="mb-0"
                        >
                            <option value="">All shops</option>
                            {shops.map((shop) => (
                                <option key={shop.id} value={shop.id}>
                                    {shop.name}
                                </option>
                            ))}
                        </AdminSelect>
                        <AdminSelect
                            aria-label="Filter by category"
                            value={filters.category}
                            onChange={(e) => applyFilters({ category: e.target.value })}
                            containerClassName="mb-0"
                        >
                            <option value="">All categories</option>
                            {Object.entries(categories).map(([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
                                </option>
                            ))}
                        </AdminSelect>
                    </>
                }
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => setEditing(row)}>
                        Edit
                    </AdminButton>
                )}
                emptyState={<AdminEmptyState icon="wallet2" title="No expenses recorded" description="Expenses you record will show up here." />}
                footer={
                    expenses.total > 0 ? (
                        <AdminPagination currentPage={expenses.current_page} totalItems={expenses.total} pageSize={expenses.per_page} onPageChange={goToPage} />
                    ) : undefined
                }
            />

            {editing && <EditExpenseModal expense={editing} categories={categories} onClose={() => setEditing(null)} />}
        </AdminShell>
    );
}
