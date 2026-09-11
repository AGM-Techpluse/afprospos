import { useEffect, useRef, useState } from 'react';
import Icon from '../Icons/Icon';

/**
 * Bell icon + panel. No notifications data source exists yet (Notifications
 * module is a later phase), so this renders a real empty state rather than
 * fabricated sample notifications — wiring a live list later is a data
 * change here, not a rebuild.
 */
export default function NotificationDropdown() {
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

    return (
        <div className="relative" ref={containerRef}>
            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                aria-label="Notifications"
                aria-expanded={open}
                className="h-8 w-8 sm:h-10 sm:w-10 rounded-customer-pill bg-white shadow-customer-soft flex items-center justify-center text-customer-text2"
            >
                <Icon name="bell" />
            </button>

            {open && (
                <div
                    role="menu"
                    className="absolute right-0 top-full mt-2 w-72 bg-white rounded-customer-card shadow-customer-strong border border-customer-divider p-4 z-50"
                >
                    <div className="flex items-center justify-between mb-3">
                        <span className="font-semibold text-customer-text text-customer-base">Notifications</span>
                    </div>
                    <div className="flex flex-col items-center text-center py-6">
                        <div className="w-10 h-10 rounded-customer-pill bg-customer-page-start flex items-center justify-center text-customer-blue text-lg mb-2">
                            <Icon name="bell-slash" />
                        </div>
                        <p className="text-customer-text2 text-customer-caption">No notifications yet.</p>
                    </div>
                </div>
            )}
        </div>
    );
}
