import AdminBadge from './AdminBadge';
import Icon from '../Icons/Icon';

interface ShopPreviewCardProps {
    name: string;
    skuPrefixCode: string;
    address: string;
    contactPhone: string;
    contactEmail: string;
    status?: 'active' | 'inactive';
}

/** Live preview of how this shop will look, built from the form's own state as it's filled in. */
export default function ShopPreviewCard({ name, skuPrefixCode, address, contactPhone, contactEmail, status }: ShopPreviewCardProps) {
    const hasName = name.trim().length > 0;

    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
            <p className="text-xs font-semibold text-admin-text3 uppercase tracking-wide mb-4">Preview</p>

            <div className="flex items-center gap-3 mb-5">
                <div className="h-12 w-12 rounded-admin-card bg-admin-blue-soft text-admin-blue flex items-center justify-center text-lg shrink-0">
                    <Icon name="shop" />
                </div>
                <div className="min-w-0">
                    <p className={`font-semibold truncate ${hasName ? 'text-admin-text' : 'text-admin-text3 italic'}`}>
                        {hasName ? name : 'New shop'}
                    </p>
                    <div className="flex items-center gap-1.5 mt-0.5">
                        {skuPrefixCode && <AdminBadge status="info">{skuPrefixCode.toUpperCase()}</AdminBadge>}
                        {status && (
                            <AdminBadge status={status === 'active' ? 'success' : 'neutral'}>
                                {status === 'active' ? 'Active' : 'Inactive'}
                            </AdminBadge>
                        )}
                    </div>
                </div>
            </div>

            <dl className="space-y-2 text-sm">
                <div className="flex justify-between gap-2">
                    <dt className="text-admin-text2 shrink-0">Address</dt>
                    <dd className="text-admin-text text-right">{address || '—'}</dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt className="text-admin-text2">Phone</dt>
                    <dd className="text-admin-text truncate">{contactPhone || '—'}</dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt className="text-admin-text2">Email</dt>
                    <dd className="text-admin-text truncate">{contactEmail || '—'}</dd>
                </div>
            </dl>
        </div>
    );
}
