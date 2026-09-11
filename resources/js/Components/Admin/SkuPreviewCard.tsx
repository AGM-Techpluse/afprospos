import Icon from '../Icons/Icon';
import AdminBadge from './AdminBadge';

interface SkuPreviewCardProps {
    brand: string;
    model: string;
    category: string;
    isSerialized: boolean;
    costPriceMinor: number;
    markupPercent: number;
    sellingPriceOverrideMinor: number | null;
}

function formatNaira(minor: number): string {
    return `₦${(minor / 100).toLocaleString('en-NG', { minimumFractionDigits: 0 })}`;
}

/** Live preview: the selling price recalculates as cost/markup are typed, exactly like the backend's Sku::calculateSellingPrice(). */
export default function SkuPreviewCard({
    brand,
    model,
    category,
    isSerialized,
    costPriceMinor,
    markupPercent,
    sellingPriceOverrideMinor,
}: SkuPreviewCardProps) {
    const hasName = brand.trim().length > 0 || model.trim().length > 0;
    const computedSellingPrice = sellingPriceOverrideMinor ?? Math.round(costPriceMinor * (1 + markupPercent / 100));

    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
            <p className="text-xs font-semibold text-admin-text3 uppercase tracking-wide mb-4">Preview</p>

            <div className="flex items-center gap-3 mb-5">
                <div className="h-12 w-12 rounded-admin-card bg-admin-blue-soft text-admin-blue flex items-center justify-center text-lg shrink-0">
                    <Icon name={isSerialized ? 'phone' : 'box-seam'} />
                </div>
                <div className="min-w-0">
                    <p className={`font-semibold truncate ${hasName ? 'text-admin-text' : 'text-admin-text3 italic'}`}>
                        {hasName ? `${brand} ${model}`.trim() : 'New product'}
                    </p>
                    <div className="flex items-center gap-1.5 mt-0.5">
                        {category && <AdminBadge status="neutral">{category}</AdminBadge>}
                        <AdminBadge status={isSerialized ? 'info' : 'neutral'}>{isSerialized ? 'Serialized' : 'Bulk'}</AdminBadge>
                    </div>
                </div>
            </div>

            <dl className="space-y-2 text-sm">
                <div className="flex justify-between gap-2">
                    <dt className="text-admin-text2">Cost price</dt>
                    <dd className="text-admin-text">{formatNaira(costPriceMinor)}</dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt className="text-admin-text2">Markup</dt>
                    <dd className="text-admin-text">{markupPercent || 0}%</dd>
                </div>
                <div className="flex justify-between gap-2 pt-2 border-t border-dashed border-admin-border">
                    <dt className="text-admin-text2 font-semibold">Selling price</dt>
                    <dd className="text-admin-text font-semibold">
                        {formatNaira(computedSellingPrice)}
                        {sellingPriceOverrideMinor !== null && <span className="text-admin-text3 font-normal"> (override)</span>}
                    </dd>
                </div>
            </dl>
        </div>
    );
}
