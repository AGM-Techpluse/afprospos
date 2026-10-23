import { ReactNode, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Icon from '../Icons/Icon';
import CustomerAvatar from './CustomerAvatar';
import ShopAside from './ShopAside';
import { CustomerToastViewport } from '../Feedback/Toast';
import { useFlashToast } from '../../hooks/useFlashToast';

interface PublicStorefrontShellProps {
    children: ReactNode;
}

/**
 * The storefront's own chrome — `/shop` and `/shop/{sku}` are guest-visible
 * (no `auth:customer` middleware), so they can't live inside `CustomerShell`
 * (which assumes an authenticated session for its sidebar/avatar/logout).
 * A signed-in customer still sees their name here via the same `auth.customer`
 * shared prop `CustomerShell` reads — same identity, lighter chrome. A
 * logged-in customer also gets a left cart aside (`ShopAside`) — search
 * and category filtering live in each page's own content instead
 * (`ShopSearchBar`), not in the aside.
 */
export default function PublicStorefrontShell({ children }: PublicStorefrontShellProps) {
    const { app_name, app_logo, auth } = usePage().props as any;
    const customerName: string | null = auth?.customer?.name ?? null;
    const avatarUrl: string | null = auth?.customer?.avatar_url ?? null;
    const [isAsideOpen, setIsAsideOpen] = useState(false);
    const showAside = customerName !== null;

    useFlashToast();

    return (
        <div data-theme="customer" className="min-h-screen bg-white">
            <header className="sticky top-0 z-30 border-b border-gray-100 bg-white">
                <div className="max-w-6xl mx-auto px-4 sm:px-8 h-16 flex items-center gap-6">
                    {showAside && (
                        <button
                            type="button"
                            onClick={() => setIsAsideOpen(true)}
                            aria-label="Open your cart"
                            className="sm:hidden text-customer-text2"
                        >
                            <Icon name="bag" className="text-xl" />
                        </button>
                    )}

                    <Link href="/" className="flex items-center gap-2 shrink-0">
                        <img src={app_logo} alt={app_name} className="h-6 w-6" />
                        <span className="font-customer-display font-extrabold text-customer-base text-customer-text">{app_name}</span>
                    </Link>

                    <nav className="hidden sm:flex items-center gap-5 text-customer-caption font-bold text-customer-text2">
                        <Link href="/" className="hover:text-customer-text">Home</Link>
                        <Link href="/shop" className="text-customer-blue">Shop</Link>
                    </nav>

                    <div className="ml-auto flex items-center gap-3">
                        {customerName !== null ? (
                            <Link href="/customer/dashboard" className="flex items-center gap-2">
                                <CustomerAvatar name={customerName} avatarUrl={avatarUrl} size={32} />
                                <span className="hidden sm:inline text-customer-caption font-bold text-customer-text">{customerName}</span>
                            </Link>
                        ) : (
                            <>
                                <Link href="/customer/login" className="text-customer-caption font-bold text-customer-text2 hover:text-customer-text">
                                    Log in
                                </Link>
                                <Link
                                    href="/customer/register"
                                    className="flex items-center gap-1.5 rounded-customer-pill bg-customer-blue text-white px-4 py-2 text-customer-caption font-bold"
                                >
                                    <Icon name="person-plus" />
                                    Sign up
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            {showAside ? (
                <div className="max-w-6xl mx-auto sm:flex sm:items-start">
                    <ShopAside isOpen={isAsideOpen} onClose={() => setIsAsideOpen(false)} />
                    <main className="flex-1 min-w-0 px-4 sm:px-8 py-8">{children}</main>
                </div>
            ) : (
                <main className="max-w-6xl mx-auto px-4 sm:px-8 py-8">{children}</main>
            )}

            <CustomerToastViewport />
        </div>
    );
}
