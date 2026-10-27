import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';

type Shop = { id: number; name: string };

interface ExpensesCreateProps {
    shops: Shop[];
    categories: Record<string, string>;
}

export default function ExpensesCreate({ shops, categories }: ExpensesCreateProps) {
    const { data, setData, post, transform, processing, errors: formErrors } = useForm({
        shop_id: '',
        category: Object.keys(categories)[0] ?? '',
        amount_naira: '',
        note: '',
    });
    // The server validates the minor-unit field (`amount_minor`) that doesn't
    // match this form's naira-bound field name.
    const errors = formErrors as Record<string, string | undefined>;

    function submit(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            shop_id: formData.shop_id ? parseInt(formData.shop_id, 10) : null,
            category: formData.category,
            amount_minor: Math.round(parseFloat(formData.amount_naira || '0') * 100),
            note: formData.note || null,
        }));

        post('/admin/finance/expenses');
    }

    return (
        <AdminShell>
            <Head title="Record expense" />

            <AdminPageHead
                title="Record expense"
                description="A one-off business cost — salaries, rent, utilities, and so on."
                backHref="/admin/finance/expenses"
                backLabel="Back to expenses"
            />

            {/* CLAUDE.md Invariant #19: a <form> never sits inside a decorative card — plain heading directly on the page background. */}
            <div className="max-w-lg">
                <form onSubmit={submit} className="flex flex-col gap-4">
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

                    <AdminSelect label="Category" value={data.category} onChange={(e) => setData('category', e.target.value)} error={errors.category}>
                        {Object.entries(categories).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </AdminSelect>

                    <AdminInput
                        label="Amount (₦)"
                        type="number"
                        step="0.01"
                        min="0"
                        value={data.amount_naira}
                        onChange={(e) => setData('amount_naira', e.target.value)}
                        error={errors.amount_minor}
                    />

                    <div>
                        <label className="block text-xs font-semibold text-admin-text mb-1.5">Note (optional)</label>
                        <textarea
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                            rows={3}
                            placeholder="e.g. October rent, Main Shop"
                            className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        />
                        {errors.note && <p className="text-xs text-admin-red mt-1">{errors.note}</p>}
                    </div>

                    <AdminButton type="submit" isLoading={processing} disabled={!data.amount_naira || parseFloat(data.amount_naira) <= 0}>
                        Record expense
                    </AdminButton>
                </form>
            </div>
        </AdminShell>
    );
}
