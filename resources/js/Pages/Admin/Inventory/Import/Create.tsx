import { ChangeEvent, DragEvent, FormEvent, useRef, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import InventorySubNav from '../../../../Components/Admin/InventorySubNav';
import Icon from '../../../../Components/Icons/Icon';

type Shop = { id: number; name: string; sku_prefix_code: string };

/**
 * Right-side shortcut for adding one product without a CSV round-trip.
 * Shares the exact same endpoint/Command as the full "New product" page
 * (Products/Create.tsx) — `isSerialized` just fixes which of that
 * Command's two modes (IMEI vs quantity) this compact form drives.
 * Collapsed behind a clickable card by default so both options fit
 * comfortably in the sidebar without competing with the CSV form.
 */
function QuickAddProductForm({ shops, isSerialized }: { shops: Shop[]; isSerialized: boolean }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, transform, processing, errors, reset } = useForm({
        brand: '',
        model: '',
        category: '',
        condition: 'new',
        cost_price_naira: '',
        markup_percent: '20',
        shop_id: shops[0]?.id.toString() ?? '',
        imei: '',
        quantity: '1',
    });

    function submit(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            brand: formData.brand,
            model: formData.model,
            category: formData.category,
            condition: formData.condition,
            is_serialized: isSerialized,
            cost_price_minor: Math.round(parseFloat(formData.cost_price_naira || '0') * 100),
            markup_percent: parseFloat(formData.markup_percent || '0'),
            selling_price_minor_override: null,
            low_stock_threshold: null,
            shop_id: parseInt(formData.shop_id, 10),
            initial_quantity: isSerialized ? null : formData.quantity ? parseInt(formData.quantity, 10) : null,
            initial_imeis: isSerialized && formData.imei.trim() ? [formData.imei.trim()] : null,
        }));

        post('/admin/inventory/products', {
            onSuccess: () => {
                reset('brand', 'model', 'category', 'imei');
                setOpen(false);
            },
        });
    }

    return (
        <div className="border border-admin-border rounded-admin-card mb-4 overflow-hidden">
            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                aria-expanded={open}
                className={`w-full flex items-center gap-2.5 px-4 py-3 text-left transition-colors ${open ? '' : 'bg-admin-blue hover:bg-blue-700'}`}
            >
                <span
                    className={`w-8 h-8 rounded-admin-button flex items-center justify-center shrink-0 ${
                        open ? 'bg-admin-hover text-admin-text2' : 'bg-white/15 text-white'
                    }`}
                >
                    <Icon name={isSerialized ? 'upc-scan' : 'box-seam'} />
                </span>
                <span className="flex-1">
                    <span className={`block text-sm font-semibold ${open ? 'text-admin-text' : 'text-white'}`}>
                        {isSerialized ? 'Add a single device' : 'Add a single item'}
                    </span>
                    <span className={`block text-xs ${open ? 'text-admin-text3' : 'text-white/80'}`}>
                        {isSerialized ? 'Serialized, tracked by IMEI' : 'Accessories and other bulk stock'}
                    </span>
                </span>
                <Icon
                    name="chevron-down"
                    className={`shrink-0 transition-transform duration-150 ${open ? 'text-admin-text3 rotate-180' : 'text-white rotate-0'}`}
                />
            </button>

            {open && (
                <form onSubmit={submit} className="px-4 pb-4 pt-1 border-t border-admin-border">
                    <AdminInput label="Brand" value={data.brand} onChange={(e) => setData('brand', e.target.value)} error={errors.brand} required />
                    <AdminInput label="Model" value={data.model} onChange={(e) => setData('model', e.target.value)} error={errors.model} required />
                    <AdminInput
                        label="Category"
                        value={data.category}
                        onChange={(e) => setData('category', e.target.value)}
                        error={errors.category}
                        required
                    />

                    <div className="grid grid-cols-2 gap-3">
                        <AdminInput
                            label="Cost price (₦)"
                            type="number"
                            step="0.01"
                            value={data.cost_price_naira}
                            onChange={(e) => setData('cost_price_naira', e.target.value)}
                            error={errors.cost_price_minor}
                            required
                        />
                        <AdminInput
                            label="Markup (%)"
                            type="number"
                            step="0.01"
                            value={data.markup_percent}
                            onChange={(e) => setData('markup_percent', e.target.value)}
                            error={errors.markup_percent}
                            required
                        />
                    </div>

                    {isSerialized ? (
                        <AdminInput
                            label="IMEI / serial number"
                            value={data.imei}
                            onChange={(e) => setData('imei', e.target.value)}
                            error={errors.imeis}
                            placeholder="15-digit IMEI"
                        />
                    ) : (
                        <AdminInput
                            label="Quantity"
                            type="number"
                            value={data.quantity}
                            onChange={(e) => setData('quantity', e.target.value)}
                        />
                    )}

                    <div className="mb-4">
                        <label className="block text-xs font-semibold text-admin-text mb-1.5">Shop</label>
                        <select
                            value={data.shop_id}
                            onChange={(e) => setData('shop_id', e.target.value)}
                            className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                        >
                            {shops.map((shop) => (
                                <option key={shop.id} value={shop.id}>
                                    {shop.name} ({shop.sku_prefix_code})
                                </option>
                            ))}
                        </select>
                    </div>

                    <AdminButton type="submit" variant="neutral" isLoading={processing}>
                        {isSerialized ? 'Add device' : 'Add item'}
                    </AdminButton>
                </form>
            )}
        </div>
    );
}

/** Same card shape as QuickAddProductForm's toggle, but a direct download rather than an expand/collapse. */
function DownloadTemplateCard() {
    return (
        <a
            href="/admin/inventory/import/template"
            className="flex items-center gap-2.5 px-4 py-3 border border-admin-border rounded-admin-card mb-4 bg-admin-blue hover:bg-blue-700 transition-colors"
        >
            <span className="w-8 h-8 rounded-admin-button bg-white/15 text-white flex items-center justify-center shrink-0">
                <Icon name="download" />
            </span>
            <span className="flex-1">
                <span className="block text-sm font-semibold text-white">Download CSV template</span>
                <span className="block text-xs text-white/80">Header + worked examples</span>
            </span>
        </a>
    );
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(0)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

interface CsvDropzoneProps {
    file: File | null;
    onSelect: (file: File | null) => void;
    error?: string;
}

/** Click-or-drag CSV picker — replaces the bare `<input type="file">` with something that shows real feedback once a file is chosen. */
function CsvDropzone({ file, onSelect, error }: CsvDropzoneProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragOver, setIsDragOver] = useState(false);

    function handleDrop(e: DragEvent<HTMLDivElement>) {
        e.preventDefault();
        setIsDragOver(false);
        onSelect(e.dataTransfer.files?.[0] ?? null);
    }

    if (file) {
        return (
            <div className="flex items-center gap-3 border border-admin-border rounded-admin-card p-3">
                <span className="w-9 h-9 rounded-admin-button bg-admin-green-soft text-admin-green flex items-center justify-center shrink-0 text-lg">
                    <Icon name="filetype-csv" />
                </span>
                <div className="flex-1 min-w-0">
                    <div className="text-sm font-medium text-admin-text truncate">{file.name}</div>
                    <div className="text-xs text-admin-text3">{formatFileSize(file.size)}</div>
                </div>
                <button
                    type="button"
                    onClick={() => {
                        onSelect(null);
                        if (inputRef.current) {
                            inputRef.current.value = '';
                        }
                    }}
                    aria-label="Remove file"
                    className="text-admin-text3 hover:text-admin-red shrink-0"
                >
                    <Icon name="x-lg" />
                </button>
                {error && <p className="text-xs text-admin-red mt-1">{error}</p>}
            </div>
        );
    }

    return (
        <div>
            <div
                onClick={() => inputRef.current?.click()}
                onDragOver={(e) => {
                    e.preventDefault();
                    setIsDragOver(true);
                }}
                onDragLeave={() => setIsDragOver(false)}
                onDrop={handleDrop}
                className={`flex flex-col items-center justify-center gap-2 border-2 border-dashed rounded-admin-card py-8 px-4 text-center cursor-pointer transition-colors ${
                    isDragOver ? 'border-admin-blue bg-admin-blue-soft' : 'border-admin-border-strong bg-admin-hover'
                }`}
            >
                <span className="w-10 h-10 rounded-full bg-admin-blue text-white flex items-center justify-center">
                    <Icon name="cloud-arrow-up" />
                </span>
                <div className="text-sm font-semibold text-admin-text">Select a CSV file to upload</div>
                <div className="text-xs text-admin-text3">or drag and drop it here</div>
                <input
                    ref={inputRef}
                    type="file"
                    accept=".csv,text/csv"
                    onChange={(e: ChangeEvent<HTMLInputElement>) => onSelect(e.target.files?.[0] ?? null)}
                    className="hidden"
                />
            </div>
            {error && <p className="text-xs text-admin-red mt-1">{error}</p>}
        </div>
    );
}

export default function ImportCreate({ shops, expectedHeader }: { shops: Shop[]; expectedHeader: string[] }) {
    const { data, setData, post, processing, errors } = useForm<{ shop_id: string; file: File | null }>({
        shop_id: shops[0]?.id.toString() ?? '',
        file: null,
    });
    const [previewRows, setPreviewRows] = useState<string[][]>([]);

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/admin/inventory/import', { forceFormData: true });
    }

    function selectFile(file: File | null) {
        setData('file', file);
        setPreviewRows([]);

        if (file) {
            const reader = new FileReader();
            reader.onload = () => {
                const text = String(reader.result ?? '');
                const lines = text.split(/\r?\n/).filter((line) => line.trim() !== '').slice(0, 6);
                setPreviewRows(lines.map((line) => line.split(',')));
            };
            reader.readAsText(file);
        }
    }

    return (
        <AdminShell>
            <Head title="Import products" />

            <AdminPageHead title="Import products" description="Bulk-add products via a CSV file (BRD INV-01)." />

            <InventorySubNav />

            <div className="grid gap-8 lg:grid-cols-[minmax(0,640px)_400px] items-start max-w-[1080px] mx-auto">
                <div className="min-w-0">
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4 mb-6">
                        <p className="text-sm font-semibold text-admin-text mb-2">Expected CSV columns</p>
                        <code className="text-xs text-admin-text2 break-all">{expectedHeader.join(',')}</code>
                        <p className="text-xs text-admin-text2 mt-2">
                            Fill either <code>quantity</code> (bulk stock) or <code>imei</code> (one serialized unit) per row, not both.
                            <code>low_stock_threshold</code> is optional.
                        </p>
                    </div>

                    <form onSubmit={submit}>
                        <div className="mb-4">
                            <label className="block text-xs font-semibold text-admin-text mb-1.5">Shop</label>
                            <select
                                value={data.shop_id}
                                onChange={(e) => setData('shop_id', e.target.value)}
                                className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                            >
                                {shops.map((shop) => (
                                    <option key={shop.id} value={shop.id}>
                                        {shop.name} ({shop.sku_prefix_code})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="mb-4">
                            <label className="block text-xs font-semibold text-admin-text mb-1.5">CSV file</label>
                            <CsvDropzone file={data.file} onSelect={selectFile} error={errors.file} />
                        </div>

                        {previewRows.length > 0 && (
                            <div className="mb-6 border border-admin-border rounded-admin-card overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="bg-admin-hover text-left">
                                            {previewRows[0].map((cell, i) => (
                                                <th key={i} className="px-2 py-1.5 font-semibold text-admin-text whitespace-nowrap">
                                                    {cell}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-admin-border">
                                        {previewRows.slice(1).map((row, i) => (
                                            <tr key={i}>
                                                {row.map((cell, j) => (
                                                    <td key={j} className="px-2 py-1.5 text-admin-text2 whitespace-nowrap">
                                                        {cell}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                <p className="text-[11px] text-admin-text3 px-2 py-1.5 border-t border-admin-border">
                                    Preview of the first {previewRows.length - 1} row(s) — the full file is parsed on import.
                                </p>
                            </div>
                        )}

                        <AdminButton type="submit" isLoading={processing} disabled={!data.file}>
                            Import
                        </AdminButton>
                    </form>
                </div>

                <div className="lg:border-l lg:border-admin-border lg:pl-8">
                    <QuickAddProductForm shops={shops} isSerialized={true} />
                    <QuickAddProductForm shops={shops} isSerialized={false} />
                    <DownloadTemplateCard />
                </div>
            </div>
        </AdminShell>
    );
}
