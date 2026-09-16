import { FormEvent, useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { useConfirm } from '../../hooks/useConfirm';
import Icon from '../../Components/Icons/Icon';
import AdminButton from '../../Components/Admin/AdminButton';
import AdminEmptyState from '../../Components/Admin/AdminEmptyState';

export type RepairPhoto = { id: number; url: string; caption: string | null; created_at: string };

interface RepairPhotoGalleryProps {
    repairId: number;
    photos: RepairPhoto[];
    disabled?: boolean;
}

/** Image evidence of the device's condition — taken at intake or any later point in the job. Stored on the public disk, immutable once uploaded (delete-only, matching the diagnosis log's "no edit" convention). */
export default function RepairPhotoGallery({ repairId, photos, disabled = false }: RepairPhotoGalleryProps) {
    const confirm = useConfirm();
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [caption, setCaption] = useState('');
    const { data, setData, post, processing, errors, reset } = useForm<{ photo: File | null; caption: string }>({
        photo: null,
        caption: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!data.photo) return;
        post(`/admin/repairs/${repairId}/photos`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('photo', 'caption');
                setCaption('');
                if (fileInputRef.current) fileInputRef.current.value = '';
            },
        });
    }

    async function deletePhoto(photoId: number) {
        const confirmed = await confirm({
            kind: 'danger',
            title: 'Delete this photo?',
            body: 'This removes the file permanently.',
            confirmLabel: 'Delete photo',
        });
        if (confirmed) {
            router.delete(`/admin/repairs/${repairId}/photos/${photoId}`, { preserveScroll: true, showProgress: false });
        }
    }

    return (
        <div>
            <h3 className="text-sm font-semibold text-admin-text mb-3">Photos</h3>

            {photos.length === 0 ? (
                <AdminEmptyState icon="camera" title="No photos yet" description="Add evidence of the device's condition." />
            ) : (
                <div className="grid grid-cols-3 gap-2 mb-3">
                    {photos.map((photo) => (
                        <div key={photo.id} className="relative group">
                            <a href={photo.url} target="_blank" rel="noopener noreferrer">
                                <img src={photo.url} alt={photo.caption ?? 'Repair photo'} className="w-full aspect-square object-cover rounded-admin-card border border-admin-border" />
                            </a>
                            {!disabled && (
                                <button
                                    type="button"
                                    onClick={() => deletePhoto(photo.id)}
                                    aria-label="Delete photo"
                                    className="absolute top-1 right-1 w-5 h-5 rounded-full bg-admin-text/70 text-white flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <Icon name="x" />
                                </button>
                            )}
                            {photo.caption && <div className="text-xs text-admin-text3 mt-1 truncate">{photo.caption}</div>}
                        </div>
                    ))}
                </div>
            )}

            {!disabled && (
                <form onSubmit={submit} className="flex items-center gap-2">
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/*"
                        onChange={(e) => setData('photo', e.target.files?.[0] ?? null)}
                        className="text-xs flex-1"
                    />
                    <input
                        type="text"
                        value={caption}
                        onChange={(e) => {
                            setCaption(e.target.value);
                            setData('caption', e.target.value);
                        }}
                        placeholder="Caption (optional)"
                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs w-32"
                    />
                    <AdminButton type="submit" variant="neutral" isLoading={processing} disabled={!data.photo}>
                        Upload
                    </AdminButton>
                </form>
            )}
            {errors.photo && <p className="text-xs text-ui-danger mt-1">{errors.photo}</p>}
        </div>
    );
}
