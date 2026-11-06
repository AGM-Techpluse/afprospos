import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import AdminModal from '../../../../Components/Admin/AdminModal';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AttributeEditor, { serializeAttributes, VariantAttributes } from '../../../../Features/Inventory/AttributeEditor';
import { Sku, toVariantAttributes } from './Show';

interface EditProductModalProps {
    onClose: () => void;
    sku: Sku;
    mode?: 'all' | 'attributes';
}

/**
 * Consolidates the product-level editors (description, specs, commission)
 * that used to sit as three separate always-expandable sections down the
 * page — each keeps its own Inertia form and its own Save button (they're
 * three independent backend endpoints), just grouped in one place instead
 * of scattered. Mounted only while open, so each open starts from the
 * current `sku` props with no stale state to reset.
 */
export default function EditProductModal({ onClose, sku, mode = 'all' }: EditProductModalProps) {
    const descriptionForm = useForm({ description: sku.description ?? '' });

    const attributesForm = useForm<{ attributes: VariantAttributes }>({ attributes: toVariantAttributes(sku.attributes) });
    const attributesErrors = attributesForm.errors as Record<string, string | undefined>;

    const commissionForm = useForm({
        is_commissionable: sku.is_commissionable,
        commission_amount_naira: sku.commission_amount_minor !== null ? String(sku.commission_amount_minor / 100) : '',
    });
    const commissionErrors = commissionForm.errors as Record<string, string | undefined>;

    function submitDescription(e: FormEvent) {
        e.preventDefault();
        descriptionForm.put(`/admin/inventory/products/${sku.id}/description`, { preserveScroll: true });
    }

    function submitAttributes(e: FormEvent) {
        e.preventDefault();
        attributesForm.transform((formData) => ({ attributes: serializeAttributes(formData.attributes) }));
        attributesForm.put(`/admin/inventory/products/${sku.id}/attributes`, {
            preserveScroll: true,
            onSuccess: () => {
                if (mode === 'attributes') {
                    onClose();
                }
            },
        });
    }

    function submitCommission(e: FormEvent) {
        e.preventDefault();
        commissionForm.transform((formData) => ({
            is_commissionable: formData.is_commissionable,
            commission_amount_minor: formData.is_commissionable && formData.commission_amount_naira
                ? Math.round(parseFloat(formData.commission_amount_naira) * 100)
                : null,
        }));
        commissionForm.put(`/admin/inventory/products/${sku.id}/commission`, { preserveScroll: true });
    }

    return (
        <AdminModal
            open
            onClose={onClose}
            title={mode === 'attributes' ? 'Edit variant attributes' : 'Edit product'}
            description={`${sku.brand} ${sku.model} · ${sku.sku_code}`}
        >
            <div className="pb-6 flex flex-col gap-6">
                {mode === 'all' && (
                    <section>
                        <h3 className="text-sm font-semibold text-admin-text mb-2">Storefront description</h3>
                        <form onSubmit={submitDescription} className="flex flex-col gap-2">
                            <textarea
                                value={descriptionForm.data.description}
                                onChange={(e) => descriptionForm.setData('description', e.target.value)}
                                rows={4}
                                maxLength={2000}
                                placeholder="What a customer browsing the storefront should know about this product…"
                                className="border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-sm outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                            />
                            <AdminButton type="submit" variant="neutral" isLoading={descriptionForm.processing} className="self-start">
                                Save description
                            </AdminButton>
                        </form>
                    </section>
                )}

                <section className={mode === 'all' ? 'pt-6 border-t border-admin-border' : ''}>
                    <h3 className="text-sm font-semibold text-admin-text mb-2">
                        {mode === 'attributes' ? 'Variant attributes' : 'Product specs'}
                    </h3>
                    <form onSubmit={submitAttributes}>
                        <AttributeEditor
                            value={attributesForm.data.attributes}
                            onChange={(value) => attributesForm.setData('attributes', value)}
                        />
                        {attributesErrors.attributes && <p className="text-xs text-admin-red mt-2">{attributesErrors.attributes}</p>}
                        <AdminButton type="submit" variant="neutral" isLoading={attributesForm.processing} className="mt-3">
                            Save specs
                        </AdminButton>
                    </form>
                </section>

                {mode === 'all' && (
                    <section className="pt-6 border-t border-admin-border">
                        <h3 className="text-sm font-semibold text-admin-text mb-2">Commission</h3>
                        <form onSubmit={submitCommission}>
                            <label className="flex items-center gap-2 text-sm text-admin-text mb-4">
                                <input
                                    type="checkbox"
                                    checked={commissionForm.data.is_commissionable}
                                    onChange={(e) => commissionForm.setData('is_commissionable', e.target.checked)}
                                />
                                Commissionable (staff earns a flat commission when this sells)
                            </label>
                            {commissionForm.data.is_commissionable && (
                                <div className="mb-4">
                                    <label className="block text-xs font-semibold text-admin-text mb-1.5">Commission amount (₦)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={commissionForm.data.commission_amount_naira}
                                        onChange={(e) => commissionForm.setData('commission_amount_naira', e.target.value)}
                                        className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                                    />
                                    {commissionErrors.commission_amount_minor && (
                                        <p className="text-xs text-admin-red mt-1">{commissionErrors.commission_amount_minor}</p>
                                    )}
                                </div>
                            )}
                            <AdminButton type="submit" isLoading={commissionForm.processing}>
                                Save commission settings
                            </AdminButton>
                        </form>
                    </section>
                )}
            </div>
        </AdminModal>
    );
}
