import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';

type Shop = { id: number; name: string };
type StaffOption = { id: number; name: string };

interface ExpensesCreateProps {
    shops: Shop[];
    categories: Record<string, string>;
    departments: Record<string, string>;
    paymentMethods: Record<string, string>;
    staffOptions: StaffOption[];
}

function todayIsoDate(): string {
    return new Date().toISOString().slice(0, 10);
}

export default function ExpensesCreate({ shops, categories, departments, paymentMethods, staffOptions }: ExpensesCreateProps) {
    const { data, setData, post, transform, processing, errors: formErrors } = useForm({
        name: '',
        shop_id: '',
        department: Object.keys(departments)[0] ?? '',
        category: Object.keys(categories)[0] ?? '',
        amount_naira: '',
        expense_date: todayIsoDate(),
        payment_method: Object.keys(paymentMethods)[0] ?? '',
        proof: null as File | null,
        description: '',
        fund_type: 'disbursement',
        reimbursed_to_staff_id: '',
    });
    // The server validates the minor-unit field (`amount_minor`) that doesn't
    // match this form's naira-bound field name.
    const errors = formErrors as Record<string, string | undefined>;

    function submit(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            ...formData,
            shop_id: formData.shop_id ? parseInt(formData.shop_id, 10) : null,
            amount_minor: Math.round(parseFloat(formData.amount_naira || '0') * 100),
            reimbursed_to_staff_id: formData.fund_type === 'reimbursement' && formData.reimbursed_to_staff_id
                ? parseInt(formData.reimbursed_to_staff_id, 10)
                : null,
        }));

        post('/admin/finance/expenses', { forceFormData: true });
    }

    return (
        <AdminShell>
            <Head title="Record expense" />

            <AdminPageHead
                title="Record expense"
                description="A business cost — salaries, rent, utilities, and so on — disbursed directly or reimbursed to a staff member."
                backHref="/admin/finance/expenses"
                backLabel="Back to expenses"
            />

            {/* CLAUDE.md Invariant #19: a <form> never sits inside a decorative card — plain heading directly on the page background. */}
            <div className="max-w-lg">
                <form onSubmit={submit} className="flex flex-col gap-4">
                    <AdminInput
                        label="Expense name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        placeholder="e.g. October office rent"
                    />

                    <AdminSelect
                        label="Shop (leave blank for a business-wide expense)"
                        value={data.shop_id}
                        onChange={(e) => setData('shop_id', e.target.value)}
                        error={errors.shop_id}
                    >
                        <option value="">Business-wide</option>
                        {shops.map((shop) => (
                            <option key={shop.id} value={shop.id}>
                                {shop.name}
                            </option>
                        ))}
                    </AdminSelect>

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
                        <label className="block text-xs font-semibold text-admin-text mb-1.5">Expense proof (optional)</label>
                        <input
                            type="file"
                            accept="image/png,image/jpeg,application/pdf"
                            onChange={(e) => setData('proof', e.target.files?.[0] ?? null)}
                            className="block w-full text-sm text-admin-text file:mr-3 file:rounded-admin-button file:border file:border-admin-border-strong file:bg-admin-surface file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-admin-text"
                        />
                        <p className="text-xs text-admin-text3 mt-1">A receipt or invoice — JPG, PNG, or PDF, up to 5MB. Only staff can view it afterward.</p>
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
                        {errors.description && <p className="text-xs text-admin-red mt-1">{errors.description}</p>}
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-admin-text mb-1.5">Fund type</label>
                        <div className="flex gap-4 text-sm text-admin-text">
                            <label className="flex items-center gap-1.5">
                                <input
                                    type="radio"
                                    checked={data.fund_type === 'disbursement'}
                                    onChange={() => setData('fund_type', 'disbursement')}
                                />
                                Disbursement (paid directly by the business)
                            </label>
                            <label className="flex items-center gap-1.5">
                                <input
                                    type="radio"
                                    checked={data.fund_type === 'reimbursement'}
                                    onChange={() => setData('fund_type', 'reimbursement')}
                                />
                                Reimbursement (pay back a staff member)
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
                            <option value="">Select a staff member…</option>
                            {staffOptions.map((staff) => (
                                <option key={staff.id} value={staff.id}>
                                    {staff.name}
                                </option>
                            ))}
                        </AdminSelect>
                    )}

                    <AdminButton type="submit" isLoading={processing} disabled={!data.name || !data.amount_naira || parseFloat(data.amount_naira) <= 0}>
                        Record expense
                    </AdminButton>
                </form>
            </div>
        </AdminShell>
    );
}
