import { FormEvent, useRef } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import CustomerInput from '../../../Components/Customer/Forms/CustomerInput';
import CustomerButton from '../../../Components/Customer/CustomerButton';
import Icon from '../../../Components/Icons/Icon';

/** The classic generic-avatar glyph (gray circle, white head + shoulders cutout) shown until the customer uploads a real photo — matches the reference default-avatar icon rather than the small blue initial-letter circle used elsewhere (that one reads fine at 40px, not at this size). */
function DefaultAvatarIcon({ size }: { size: number }) {
    return (
        <svg viewBox="0 0 100 100" width={size} height={size} aria-hidden="true">
            <defs>
                <clipPath id="default-avatar-clip">
                    <circle cx="50" cy="50" r="50" />
                </clipPath>
            </defs>
            <g clipPath="url(#default-avatar-clip)">
                <rect width="100" height="100" fill="#D1D5DB" />
                <circle cx="50" cy="40" r="18" fill="white" />
                <ellipse cx="50" cy="102" rx="32" ry="30" fill="white" />
            </g>
        </svg>
    );
}

export default function CustomerProfileShow() {
    const { auth } = usePage().props as any;
    const customerName: string = auth?.customer?.name ?? '';
    const avatarUrl: string | null = auth?.customer?.avatar_url ?? null;
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, put, processing, errors, reset } = useForm({
        name: customerName,
        phone: auth?.customer?.phone ?? '',
        email: auth?.customer?.email ?? '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        put('/customer/profile');
    }

    function onAvatarSelected(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;

        router.post('/customer/profile/avatar', { avatar: file }, { forceFormData: true });
        e.target.value = '';
    }

    function removeAvatar() {
        router.delete('/customer/profile/avatar');
    }

    return (
        <CustomerShell>
            <Head title="My profile" />

            {/* order-2 as the base (mobile) value, sm:order-1 overriding at desktop — order must be set at BOTH breakpoints, not just sm:, or mobile falls back to DOM order (form first) which was the actual bug: avatar only visible after scrolling past the whole form. */}
            <div className="customer-content-grid py-4 sm:py-0">
                <div className="order-2 sm:order-1">
                    <h1 className="font-customer-display font-extrabold text-customer-2xl text-customer-text mb-1">My profile</h1>
                    <p className="text-customer-text2 text-customer-caption mb-6">
                        Keep your contact details up to date so we can reach you about repairs and orders.
                    </p>

                    {/* No elevated card wrapper — forms sit directly on the page (UI/UX §10.1). */}
                    <form onSubmit={submit} className="max-w-md">
                        <CustomerInput
                            label="Full name"
                            value={data.name}
                            onChange={(e: any) => setData('name', e.target.value)}
                            error={errors.name}
                        />
                        <CustomerInput
                            label="Phone number"
                            value={data.phone}
                            onChange={(e: any) => setData('phone', e.target.value)}
                            error={errors.phone}
                        />
                        <CustomerInput
                            label="Email"
                            type="email"
                            value={data.email}
                            onChange={(e: any) => setData('email', e.target.value)}
                            error={errors.email}
                        />

                        <div className="flex items-center gap-2 mt-2">
                            <CustomerButton type="submit" isLoading={processing}>
                                Save changes
                            </CustomerButton>
                            <CustomerButton type="button" variant="neutral" onClick={() => reset()}>
                                Cancel
                            </CustomerButton>
                        </div>
                    </form>
                </div>

                <div className="flex flex-col items-center text-center py-4 order-1 sm:order-2">
                    <div className="relative">
                        {avatarUrl ? (
                            <img
                                src={avatarUrl}
                                alt={customerName}
                                className="w-44 h-44 rounded-customer-pill object-cover shadow-customer-soft"
                            />
                        ) : (
                            <div className="rounded-customer-pill overflow-hidden shadow-customer-soft">
                                <DefaultAvatarIcon size={176} />
                            </div>
                        )}

                        <button
                            type="button"
                            onClick={() => fileInputRef.current?.click()}
                            aria-label="Upload profile photo"
                            className="absolute bottom-1 right-1 w-11 h-11 rounded-customer-pill bg-customer-blue text-white flex items-center justify-center shadow-customer-strong"
                        >
                            <Icon name="camera-fill" />
                        </button>
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept="image/*"
                            onChange={onAvatarSelected}
                            className="hidden"
                        />
                    </div>

                    <div className="font-bold text-customer-lg text-customer-text mt-4">{customerName || 'Your profile'}</div>
                    <p className="text-customer-caption text-customer-text2 mt-1 max-w-[220px]">
                        A clear photo helps our staff recognize you when you visit a shop.
                    </p>

                    {avatarUrl && (
                        <button
                            type="button"
                            onClick={removeAvatar}
                            className="text-customer-caption font-semibold text-customer-red mt-3"
                        >
                            Remove photo
                        </button>
                    )}
                </div>
            </div>
        </CustomerShell>
    );
}
