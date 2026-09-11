import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import Icon from '../Icons/Icon';
import { initialsFor } from '../../lib/initials';

export default function AdminAccountMenu({ name, email }: { name: string; email: string }) {
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;

        const handleClickOutside = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        const handleEscape = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setOpen(false);
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [open]);

    function logout() {
        router.post('/staff/logout');
    }

    return (
        <div className="relative" ref={containerRef}>
            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                aria-label="Account menu"
                aria-expanded={open}
                className="h-[26px] w-[26px] rounded-full bg-admin-blue text-white text-[11px] font-bold flex items-center justify-center shrink-0"
            >
                {initialsFor(name)}
            </button>

            {open && (
                <div
                    role="menu"
                    className="absolute right-0 top-full mt-2 w-56 bg-admin-surface rounded-admin-card border border-admin-border py-1 z-50"
                >
                    <div className="px-3 py-2 border-b border-admin-border">
                        <p className="text-sm font-semibold text-admin-text truncate">{name}</p>
                        <p className="text-xs text-admin-text2 truncate">{email}</p>
                    </div>
                    <button
                        type="button"
                        onClick={logout}
                        className="w-full flex items-center gap-2 px-3 py-2 text-sm text-admin-text hover:bg-admin-hover"
                    >
                        <Icon name="box-arrow-right" />
                        Log out
                    </button>
                </div>
            )}
        </div>
    );
}
