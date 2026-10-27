import { Link, usePage } from '@inertiajs/react';

const TABS = [
    { key: 'products', label: 'Products', href: '/admin/inventory/products' },
    { key: 'parts', label: 'Parts', href: '/admin/inventory/products?purpose=parts' },
    { key: 'stock', label: 'Stock', href: '/admin/inventory/stock' },
    { key: 'serialized-units', label: 'Serialized units', href: '/admin/inventory/serialized-units' },
    { key: 'transfers', label: 'Transfers', href: '/admin/inventory/transfers' },
    { key: 'reconciliation', label: 'Reconciliation', href: '/admin/inventory/reconciliation' },
    { key: 'import', label: 'Import', href: '/admin/inventory/import' },
];

/** Sub-navigation between Inventory's four areas — they share one sidebar entry (AdminShell), so this is how each area is actually reached. */
export default function InventorySubNav() {
    const { url } = usePage();

    return (
        <div
            className="flex items-center gap-1 border-b border-admin-border mb-6 -mt-1 overflow-x-auto"
            style={{ WebkitOverflowScrolling: 'touch', overscrollBehaviorX: 'contain' }}
        >

            {TABS.map((tab) => {
                const active = url === tab.href || url.startsWith(`${tab.href}/`);

                return (
                    <Link
                        key={tab.key}
                        href={tab.href}
                        className={`shrink-0 whitespace-nowrap px-3 py-2 text-sm font-medium border-b-2 -mb-px transition-colors ${
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
