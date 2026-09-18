import { Link, usePage } from '@inertiajs/react';

const TABS = [
    { key: 'device-catalog', label: 'Device catalog', href: '/admin/settings/device-catalog' },
    { key: 'notifications', label: 'Notifications', href: '/admin/settings/notifications' },
];

/** Sub-navigation between Settings' areas — they share one sidebar entry (AdminShell), same pattern as WarrantySubNav/InventorySubNav. */
export default function SettingsSubNav() {
    const { url } = usePage();

    return (
        <div className="flex items-center gap-1 border-b border-admin-border mb-6 -mt-1">
            {TABS.map((tab) => {
                const active = url === tab.href || url.startsWith(`${tab.href}/`);

                return (
                    <Link
                        key={tab.key}
                        href={tab.href}
                        className={`px-3 py-2 text-sm font-medium border-b-2 -mb-px transition-colors ${
                            active
                                ? 'border-admin-blue text-admin-blue'
                                : 'border-transparent text-admin-text2 hover:text-admin-text'
                        }`}
                    >
                        {tab.label}
                    </Link>
                );
            })}
        </div>
    );
}
