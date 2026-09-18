import { Link, usePage } from '@inertiajs/react';

const TABS = [
    { key: 'claims', label: 'Claims', href: '/admin/warranty/claims' },
    { key: 'returns', label: 'Returns', href: '/admin/warranty/returns' },
    { key: 'trade-ins', label: 'Trade-ins', href: '/admin/warranty/trade-ins' },
];

/** Sub-navigation between Warranty's three areas — they share one sidebar entry (AdminShell), so this is how each area is actually reached. */
export default function WarrantySubNav() {
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
