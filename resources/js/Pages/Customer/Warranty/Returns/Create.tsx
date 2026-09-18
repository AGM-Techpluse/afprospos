import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import CustomerShell from '../../../../Components/Customer/CustomerShell';
import CustomerSelect from '../../../../Components/Customer/Forms/CustomerSelect';
import CustomerButton from '../../../../Components/Customer/CustomerButton';
import CustomerEmptyState from '../../../../Components/Customer/CustomerEmptyState';
import { formatNaira } from '../../../../lib/money';

interface SaleOption {
    id: number;
    invoice_number?: string;
    total_minor: number;
    created_at: string;
}

interface ReturnRequestCreateProps {
    saleId: number | null;
    recentSales: SaleOption[];
}

export default function CustomerReturnRequestCreate({ saleId, recentSales }: ReturnRequestCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        sale_id: saleId?.toString() ?? recentSales[0]?.id.toString() ?? '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/customer/warranty/returns');
    }

    return (
        <CustomerShell>
            <Head title="Start a return" />

            <h1 className="font-customer-display font-extrabold text-customer-2xl text-customer-text mb-1">Start a return</h1>
            <p className="text-customer-text2 text-customer-caption mb-6">
                Tell us which purchase you'd like to return — our team will review it and get back to you.
            </p>

            {recentSales.length === 0 ? (
                <CustomerEmptyState
                    icon="bag"
                    description="You don't have any completed purchases to return yet."
                    className="bg-white rounded-customer-card shadow-customer-soft max-w-md"
                />
            ) : (
                /* No elevated card wrapper — forms sit directly on the page (UI/UX §10.1). */
                <form onSubmit={submit} className="max-w-md">
                    <CustomerSelect
                        label="Purchase"
                        value={data.sale_id}
                        onChange={(e) => setData('sale_id', e.target.value)}
                        error={errors.sale_id}
                    >
                        {recentSales.map((sale) => (
                            <option key={sale.id} value={sale.id}>
                                {sale.invoice_number ?? `Sale #${sale.id}`} — {formatNaira(sale.total_minor)} ({new Date(sale.created_at).toLocaleDateString()})
                            </option>
                        ))}
                    </CustomerSelect>

                    <div className="flex items-center gap-2 mt-2">
                        <CustomerButton type="submit" isLoading={processing}>
                            Submit return request
                        </CustomerButton>
                    </div>
                </form>
            )}
        </CustomerShell>
    );
}
