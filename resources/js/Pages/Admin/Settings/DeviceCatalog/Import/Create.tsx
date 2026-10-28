import { ChangeEvent, DragEvent, FormEvent, useMemo, useRef, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminShell from '../../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../../Components/Admin/AdminButton';
import Icon from '../../../../../Components/Icons/Icon';

/** Same card shape as Inventory's import page — a direct download, not an expand/collapse. */
function DownloadTemplateCard() {
    return (
        <a
            href="/admin/settings/device-catalog/import/template"
            className="flex items-center gap-2.5 px-4 py-3 border border-admin-border rounded-admin-card mb-4 bg-admin-blue hover:bg-admin-blue-hover transition-colors"
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

/** Handles quoted fields (so a comma inside a value doesn't split into an extra column) — the naive `line.split(',')` this replaced did not. */
function parseCsvLine(line: string): string[] {
    const cells: string[] = [];
    let current = '';
    let inQuotes = false;

    for (let i = 0; i < line.length; i++) {
        const char = line[i];

        if (inQuotes) {
            if (char === '"') {
                if (line[i + 1] === '"') {
                    current += '"';
                    i++;
                } else {
                    inQuotes = false;
                }
            } else {
                current += char;
            }
        } else if (char === '"') {
            inQuotes = true;
        } else if (char === ',') {
            cells.push(current);
            current = '';
        } else {
            current += char;
        }
    }

    cells.push(current);

    return cells;
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

export default function DeviceCatalogImportCreate({ expectedHeader }: { expectedHeader: string[] }) {
    const { data, setData, post, processing, errors } = useForm<{ file: File | null }>({ file: null });
    const [csvRows, setCsvRows] = useState<string[][]>([]);
    const [previewSearch, setPreviewSearch] = useState('');

    const dataRows = csvRows.slice(1);
    const filteredRows = useMemo(() => {
        const term = previewSearch.trim().toLowerCase();

        if (term === '') {
            return dataRows;
        }

        return dataRows.filter((row) => row.some((cell) => cell.toLowerCase().includes(term)));
    }, [dataRows, previewSearch]);

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/admin/settings/device-catalog/import', { forceFormData: true });
    }

    function selectFile(file: File | null) {
        setData('file', file);
        setCsvRows([]);
        setPreviewSearch('');

        if (file) {
            const reader = new FileReader();
            reader.onload = () => {
                const text = String(reader.result ?? '');
                const lines = text.split(/\r?\n/).filter((line) => line.trim() !== '');
                setCsvRows(lines.map(parseCsvLine));
            };
            reader.readAsText(file);
        }
    }

    return (
        <AdminShell>
            <Head title="Import device catalog" />

            <Link href="/admin/settings/device-catalog" className="inline-flex items-center gap-1 text-xs font-semibold text-admin-blue mb-3">
                <Icon name="chevron-left" />
                Back to device catalog
            </Link>

            <AdminPageHead title="Import device catalog" description="Bulk-add device types, brands, models, and fixable faults via a CSV file." />

            <div className="grid gap-8 lg:grid-cols-[minmax(0,640px)_320px] items-start max-w-[1000px] mx-auto">
                <div className="min-w-0">
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4 mb-6">
                        <p className="text-sm font-semibold text-admin-text mb-2">Expected CSV columns</p>
                        <code className="text-xs text-admin-text2 break-all">{expectedHeader.join(',')}</code>
                        <p className="text-xs text-admin-text2 mt-2">
                            <code>device_type</code>, <code>icon</code>, and <code>brand</code> are required on every row. <code>model</code> is optional — leave it
                            blank to only ensure the type/brand exist. <code>fault_label</code> is independently optional; when given, it creates a
                            model-specific fault if <code>model</code> is also filled on that row, otherwise a type-level default.
                            Re-importing the same file is safe — existing types/brands/models/faults are reused, not duplicated.
                        </p>
                    </div>

                    <form onSubmit={submit}>
                        <div className="mb-4">
                            <label className="block text-xs font-semibold text-admin-text mb-1.5">CSV file</label>
                            <CsvDropzone file={data.file} onSelect={selectFile} error={errors.file} />
                        </div>

                        {csvRows.length > 0 && (
                            <div className="mb-6 border border-admin-border rounded-admin-card overflow-hidden">
                                <div className="p-2 border-b border-admin-border">
                                    <div className="relative">
                                        <span className="absolute left-2.5 top-1/2 -translate-y-1/2 text-admin-text3">
                                            <Icon name="search" />
                                        </span>
                                        <input
                                            type="text"
                                            value={previewSearch}
                                            onChange={(e) => setPreviewSearch(e.target.value)}
                                            placeholder={`Search ${dataRows.length} row(s)...`}
                                            className="w-full border border-admin-border-strong rounded-admin-button pl-8 pr-2.5 py-1.5 text-xs"
                                        />
                                    </div>
                                </div>
                                <div className="overflow-auto max-h-72">
                                    <table className="w-full text-xs">
                                        <thead>
                                            <tr className="bg-admin-hover text-left sticky top-0">
                                                {csvRows[0].map((cell, i) => (
                                                    <th key={i} className="px-2 py-1.5 font-semibold text-admin-text whitespace-nowrap">
                                                        {cell}
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-admin-border">
                                            {filteredRows.map((row, i) => (
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
                                    {filteredRows.length === 0 && (
                                        <p className="text-xs text-admin-text3 text-center py-4">No rows match "{previewSearch}".</p>
                                    )}
                                </div>
                                <p className="text-[11px] text-admin-text3 px-2 py-1.5 border-t border-admin-border">
                                    {previewSearch
                                        ? `Showing ${filteredRows.length} of ${dataRows.length} row(s) matching your search.`
                                        : `${dataRows.length} row(s) in this file.`}{' '}
                                    The entire file is imported, not just what's shown here.
                                </p>
                            </div>
                        )}

                        <AdminButton type="submit" isLoading={processing} disabled={!data.file}>
                            Import
                        </AdminButton>
                    </form>
                </div>

                <div className="lg:border-l lg:border-admin-border lg:pl-8">
                    <DownloadTemplateCard />
                </div>
            </div>
        </AdminShell>
    );
}
