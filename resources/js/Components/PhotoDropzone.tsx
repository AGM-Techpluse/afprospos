import { ChangeEvent, DragEvent, useEffect, useRef, useState } from 'react';
import Icon from './Icons/Icon';

interface PhotoDropzoneProps {
    file: File | null;
    onSelect: (file: File | null) => void;
    error?: string;
    label?: string;
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

/** Click-or-drag photo picker — same shape as the CSV import dropzone, with a camera icon and an actual image preview once a file is chosen instead of a generic file-type icon. */
export default function PhotoDropzone({ file, onSelect, error, label = 'Select a photo to upload' }: PhotoDropzoneProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragOver, setIsDragOver] = useState(false);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);

    useEffect(() => {
        if (!file) {
            setPreviewUrl(null);
            return;
        }

        const url = URL.createObjectURL(file);
        setPreviewUrl(url);
        return () => URL.revokeObjectURL(url);
    }, [file]);

    function handleDrop(e: DragEvent<HTMLDivElement>) {
        e.preventDefault();
        setIsDragOver(false);
        onSelect(e.dataTransfer.files?.[0] ?? null);
    }

    if (file) {
        return (
            <div className="flex items-center gap-3 border border-admin-border rounded-admin-card p-3">
                {previewUrl ? (
                    <img src={previewUrl} alt={file.name} className="w-9 h-9 rounded-admin-button object-cover shrink-0" />
                ) : (
                    <span className="w-9 h-9 rounded-admin-button bg-admin-blue-soft text-admin-blue flex items-center justify-center shrink-0 text-lg">
                        <Icon name="camera" />
                    </span>
                )}
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
                    aria-label="Remove photo"
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
                    <Icon name="camera" />
                </span>
                <div className="text-sm font-semibold text-admin-text">{label}</div>
                <div className="text-xs text-admin-text3">or drag and drop it here</div>
                <input
                    ref={inputRef}
                    type="file"
                    accept="image/*"
                    onChange={(e: ChangeEvent<HTMLInputElement>) => onSelect(e.target.files?.[0] ?? null)}
                    className="hidden"
                />
            </div>
            {error && <p className="text-xs text-admin-red mt-1">{error}</p>}
        </div>
    );
}
