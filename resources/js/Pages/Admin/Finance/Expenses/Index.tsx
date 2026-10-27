import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../../Components/Admin/AdminPagination';
import AdminStatCard from '../../../../Components/Admin/AdminStatCard';
import AdminBadge from '../../../../Components/Admin/AdminBadge';
import AdminModal from '../../../../Components/Admin/AdminModal';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../../lib/money';

type ExpenseRow = {
    id: number;
    name: string;
    shop_id: number | null;
    shop_name: string | null;
    department: string;
    category: string;
    amount_minor: number;
    expense_date: string;
    payment_method: string;
    has_proof: boolean;
    description: string | null;
    fund_type: 'disbursement' | 'reimbursement';
    reimbursed_to_staff_name: string | null;
    recorded_by_staff_name: string | null;
    created_at: string;
};

type Shop = { id: number; name: string };
type StaffOption = { id: number; name: string };
type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface ExpensesIndexProps {
    expenses: Paginated<ExpenseRow>;
    totalMinor: number;
    filters: { shop_id: number | null; category: string; department: string };
    shops: Shop[];
    categories: Record<string, string>;
    departments: Record<string, string>;
    paymentMethods: Record<string, string>;
    staffOptions: StaffOption[];
}

function todayIsoDate(): string {
    return new Date().toISOString().slice(0, 10);
}

function EditExpenseModal({
    expense,
    categories,
    departments,
    paymentMethods,
    staffOptions,
    onClose,
}: {
    expense: ExpenseRow;
    categories: Record<string, string>;
    departments: Record<string, string>;
    paymentMethods: Record<string, string>;
    staffOptions: StaffOption[];
    onClose: () => void;
}) {
    const { data, setData, put, transform, processing, errors: formErrors } = useForm({
        name: expense.name,
        department: expense.department,
        category: expense.category,
        amount_naira: String(expense.amount_minor / 100),
        expense_date: expense.expense_date,
        payment_method: expense.payment_method,
        proof: null as File | null,
        description: expense.description ?? '',
        fund_type: expense.fund_type,
        reimbursed_to_staff_id: '',
    });
    // The server validates the minor-unit field (`amount_minor`) that doesn't
    // match this form's naira-bound field name.
    const errors = formErrors as Record<string, string | undefined>;

    function submit(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            ...formData,
            amount_minor: Math.round(parseFloat(formData.amount_naira || '0') * 100),
            reimbursed_to_staff_id: formData.fund_type === 'reimbursement' && formData.reimbursed_to_staff_id
                ? parseInt(formData.reimbursed_to_staff_id, 10)
                : null,
        }));

        put(`/admin/finance/expenses/${expense.id}`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    }

    return (
        <AdminModal open title="Edit expense" onClose={onClose}>
            <form id="edit-expense-form" onSubmit={submit} className="pb-6 flex flex-col gap-4">
                <AdminInput label="Expense name" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} />

                <div className="grid grid-cols-2 gap-3">
                    <AdminSelect label="Department" value={data.department} onChange={(e) => setData('department', e.target.value)} error={errors.department}>
                        {Object.entries(departments).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </AdminSelect>
                    <AdminSelect label="Category" value={data.category} onChange={(e) => setData('category', e.target.value)} error={errors.category}>
                        {Object.entries(categories).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </AdminSelect>
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <AdminInput
                        label="Amount (₦)"
                        type="number"
                        step="0.01"
                        min="0"
                        value={data.amount_naira}
                        onChange={(e) => setData('amount_naira', e.target.value)}
                        error={errors.amount_minor}
                    />
                    <AdminInput
                        label="Expense date"
                        type="date"
                        max={todayIsoDate()}
                        value={data.expense_date}
                        onChange={(e) => setData('expense_date', e.target.value)}
                        error={errors.expense_date}
                    />
                </div>

                <AdminSelect label="Payment method" value={data.payment_method} onChange={(e) => setData('payment_method', e.target.value)} error={errors.payment_method}>
                    {Object.entries(paymentMethods).map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </AdminSelect>

                <div>
                    <label className="block text-xs font-semibold text-admin-text mb-1.5">
                        Replace expense proof {expense.has_proof ? '(optional — a document is already attached)' : '(optional)'}
                    </label>
                    <input
                        type="file"
                        accept="image/png,image/jpeg,application/pdf"
                        onChange={(e) => setData('proof', e.target.files?.[0] ?? null)}
                        className="block w-full text-sm text-admin-text file:mr-3 file:rounded-admin-button file:border file:border-admin-border-strong file:bg-admin-surface file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-admin-text"
                    />
                    {errors.proof && <p className="text-xs text-admin-red mt-1">{errors.proof}</p>}
                </div>

                <div>
                    <label className="block text-xs font-semibold text-admin-text mb-1.5">Description (optional)</label>
                    <textarea
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        rows={3}
                        className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                    />
                </div>

                <div>
                    <label className="block text-xs font-semibold text-admin-text mb-1.5">Fund type</label>
                    <div className="flex gap-4 text-sm text-admin-text">
                        <label className="flex items-center gap-1.5">
                            <input type="radio" checked={data.fund_type === 'disbursement'} onChange={() => setData('fund_type', 'disbursement')} />
                            Disbursement
                        </label>
                        <label className="flex items-center gap-1.5">
                            <input type="radio" checked={data.fund_type === 'reimbursement'} onChange={() => setData('fund_type', 'reimbursement')} />
                            Reimbursement
                        </label>
                    </div>
                </div>

                {data.fund_type === 'reimbursement' && (
                    <AdminSelect
                        label="Reimburse to"
                        value={data.reimbursed_to_staff_id}
                        onChange={(e) => setData('reimbursed_to_staff_id', e.target.value)}
                        error={errors.reimbursed_to_staff_id}
                    >
                        <option value="">{expense.reimbursed_to_staff_name ?? 'Select a staff member…'}</option>
                        {staffOptions.map((staff) => (
                            <option key={staff.id} value={staff.id}>
                                {staff.name}
                            </option>
                        ))}
                    </AdminSelect>
                )}

                <AdminButton type="submit" isLoading={processing}>
                    Save changes
                </AdminButton>
            </form>
        </AdminModal>
    );
}

const FUND_TYPE_BADGE = { disbursement: 'neutral', reimbursement: 'info' } as const;

export default function ExpensesIndex({ expenses, totalMinor, filters, shops, categories, departments, paymentMethods, staffOptions }: ExpensesIndexProps) {
    const [editing, setEditing] = useState<ExpenseRow | null>(null);

    function applyFilters(next: Partial<{ shop_id: number | null; category: string; department: string }>) {
        router.get(
            '/admin/finance/expenses',
            {
                shop_id: next.shop_id !== undefined ? (next.shop_id ?? '') : (filters.shop_id ?? ''),
                category: next.category ?? filters.category,
                department: next.department ?? filters.department,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function goToPage(page: number) {
        router.get(
            '/admin/finance/expenses',
            { shop_id: filters.shop_id ?? '', category: filters.category || undefined, department: filters.department || undefined, page },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const columns: DataTableColumn<ExpenseRow>[] = [
        { key: 'expense_date', label: 'Date', render: (row) => new Date(row.expense_date).toLocaleDateString() },
        { key: 'name', label: 'Name' },
        { key: 'department', label: 'Department', render: (row) => departments[row.department] ?? row.department },
        { key: 'category', label: 'Category', render: (row) => categories[row.category] ?? row.category },
        { key: 'amount_minor', label: 'Amount', render: (row) => formatNaira(row.amount_minor) },
        {
            key: 'fund_type',
            label: 'Fund type',
            render: (row) => (
                <AdminBadge status={FUND_TYPE_BADGE[row.fund_type]}>
                    {row.fund_type === 'reimbursement' ? `Reimburse — ${row.reimbursed_to_staff_name ?? 'staff'}` : 'Disbursement'}
                </AdminBadge>
            ),
        },
        {
            key: 'has_proof',
            label: 'Proof',
            render: (row) =>
                row.has_proof ? (
                    <a href={`/admin/finance/expenses/${row.id}/proof`} className="text-xs font-semibold text-admin-blue">
                        Download
                    </a>
                ) : (
                    <span className="text-admin-text3">—</span>
                ),
        },
    ];

    return (
        <AdminShell>
            <Head title="Expenses" />

            <AdminPageHead
                title="Expenses"
                description="Business costs — salaries, rent, utilities, and other disbursed or reimbursed expenses."
                actions={<AdminButton onClick={() => router.visit('/admin/finance/expenses/create')}>Record expense</AdminButton>}
            />

            <div className="mb-6 max-w-xs">
                <AdminStatCard label="Total (this filter)" value={formatNaira(totalMinor)} variant="blue" fillHeight />
            </div>

            <ResponsiveDataTable
                columns={columns}
                rows={expenses.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="name"
                mobileStatusField="amount_minor"
                mobileDetailFields={['shop_name', 'department', 'expense_date']}
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
                            aria-label="Filter by department"
                            value={filters.department}
                            onChange={(e) => applyFilters({ department: e.target.value })}
                            containerClassName="mb-0"
                        >
                            <option value="">All departments</option>
                            {Object.entries(departments).map(([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
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

            {editing && (
                <EditExpenseModal
                    expense={editing}
                    categories={categories}
                    departments={departments}
                    paymentMethods={paymentMethods}
                    staffOptions={staffOptions}
                    onClose={() => setEditing(null)}
                />
            )}
        </AdminShell>
    );
}
