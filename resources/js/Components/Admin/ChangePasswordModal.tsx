import { FormEvent, useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import AdminModal from './AdminModal';
import AdminButton from './AdminButton';
import AdminInput from './Forms/AdminInput';
import Icon from '../Icons/Icon';

interface ChangePasswordModalProps {
    open: boolean;
    onClose: () => void;
}

function ShowPasswordToggle({ shown, onToggle }: { shown: boolean; onToggle: () => void }) {
    return (
        <button
            type="button"
            onClick={onToggle}
            tabIndex={-1}
            aria-label={shown ? 'Hide password' : 'Show password'}
            className="text-admin-text3 hover:text-admin-text"
        >
            <Icon name={shown ? 'eye-slash' : 'eye'} />
        </button>
    );
}

export default function ChangePasswordModal({ open, onClose }: ChangePasswordModalProps) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const [showCurrent, setShowCurrent] = useState(false);
    const [showNew, setShowNew] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    useEffect(() => {
        if (!open) {
            reset();
            clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/admin/account/password', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    }

    return (
        <AdminModal open={open} onClose={onClose} title="Change password" description="Choose a new password for your own account.">
            <form onSubmit={submit} id="change-password-form" className="pb-6 flex flex-col gap-3">
                <AdminInput
                    label="Current password"
                    type={showCurrent ? 'text' : 'password'}
                    autoComplete="current-password"
                    value={data.current_password}
                    onChange={(e) => setData('current_password', e.target.value)}
                    error={errors.current_password}
                    rightElement={<ShowPasswordToggle shown={showCurrent} onToggle={() => setShowCurrent((current) => !current)} />}
                />
                <AdminInput
                    label="New password"
                    type={showNew ? 'text' : 'password'}
                    autoComplete="new-password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    rightElement={<ShowPasswordToggle shown={showNew} onToggle={() => setShowNew((current) => !current)} />}
                />
                <AdminInput
                    label="Confirm new password"
                    type={showConfirm ? 'text' : 'password'}
                    autoComplete="new-password"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    rightElement={<ShowPasswordToggle shown={showConfirm} onToggle={() => setShowConfirm((current) => !current)} />}
                />
                <AdminButton type="submit" isLoading={processing}>
                    Update password
                </AdminButton>
            </form>
        </AdminModal>
    );
}
