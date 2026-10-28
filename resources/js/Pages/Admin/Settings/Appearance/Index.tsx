import { CSSProperties, FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import SettingsSubNav from '../../../../Components/Admin/SettingsSubNav';
import AdminButton from '../../../../Components/Admin/AdminButton';
import { useConfirm } from '../../../../hooks/useConfirm';
import { paletteVars, WELCOME_PALETTE_TOKENS, WelcomePalette } from '../../../../lib/welcomePalette';

interface SiteAppearanceProps {
    palette: WelcomePalette;
    defaults: WelcomePalette;
    logoUrl: string;
    hasCustomLogo: boolean;
    faviconUrl: string;
    hasCustomFavicon: boolean;
    adminThemeAccent: string;
    adminThemeDefault: string;
}

const HEX = /^#[0-9A-Fa-f]{6}$/;

/** The sidebar/topbar/receipt logo — a separate concern from the landing page colours below, but lives on the same Settings → Site appearance page. */
function SiteLogoSection({ logoUrl, hasCustomLogo }: { logoUrl: string; hasCustomLogo: boolean }) {
    const confirm = useConfirm();
    const { data, setData, post, processing, errors, reset } = useForm<{ logo: File | null }>({ logo: null });
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);

    function pickFile(file: File | null) {
        setData('logo', file);
        setPreviewUrl(file ? URL.createObjectURL(file) : null);
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!data.logo) return;

        post('/admin/settings/appearance/logo', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setPreviewUrl(null);
            },
        });
    }

    async function resetLogo() {
        const confirmed = await confirm({
            kind: 'warning',
            title: 'Reset the site logo?',
            body: 'Goes back to the default AfProsPos logo.',
            confirmLabel: 'Reset',
        });

        if (confirmed) {
            router.delete('/admin/settings/appearance/logo', { preserveScroll: true });
        }
    }

    return (
        <div className="mb-8 pb-8 border-b border-admin-border">
            <h2 className="text-sm font-semibold text-admin-text mb-3">Site logo</h2>
            <form onSubmit={submit} className="flex items-center gap-4">
                <img
                    src={previewUrl ?? logoUrl}
                    alt="Current site logo"
                    className="h-12 w-12 rounded-admin-button border border-admin-border object-contain bg-admin-surface p-1"
                />
                <div className="flex-1 min-w-0">
                    <input
                        type="file"
                        accept="image/png,image/jpeg,image/svg+xml,image/webp"
                        onChange={(e) => pickFile(e.target.files?.[0] ?? null)}
                        className="block w-full text-sm text-admin-text file:mr-3 file:rounded-admin-button file:border file:border-admin-border-strong file:bg-admin-surface file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-admin-text"
                    />
                    {errors.logo && <p className="text-xs text-admin-red mt-1">{errors.logo}</p>}
                    <p className="text-xs text-admin-text3 mt-1">PNG, JPG, SVG, or WebP — shown small (a square icon reads best), up to 2MB.</p>
                </div>
                <div className="flex items-center gap-2 shrink-0">
                    <AdminButton type="submit" isLoading={processing} disabled={!data.logo}>
                        Save logo
                    </AdminButton>
                    {hasCustomLogo && (
                        <AdminButton type="button" variant="danger" onClick={resetLogo}>
                            Reset
                        </AdminButton>
                    )}
                </div>
            </form>
        </div>
    );
}

/** The browser-tab icon — same upload mechanism as the site logo above, a separate file so a square favicon and a wider logo don't have to compromise on the same image. */
function SiteFaviconSection({ faviconUrl, hasCustomFavicon }: { faviconUrl: string; hasCustomFavicon: boolean }) {
    const confirm = useConfirm();
    const { data, setData, post, processing, errors, reset } = useForm<{ favicon: File | null }>({ favicon: null });
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);

    function pickFile(file: File | null) {
        setData('favicon', file);
        setPreviewUrl(file ? URL.createObjectURL(file) : null);
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!data.favicon) return;

        post('/admin/settings/appearance/favicon', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setPreviewUrl(null);
            },
        });
    }

    async function resetFavicon() {
        const confirmed = await confirm({
            kind: 'warning',
            title: 'Reset the favicon?',
            body: 'Goes back to the default AfProsPos favicon.',
            confirmLabel: 'Reset',
        });

        if (confirmed) {
            router.delete('/admin/settings/appearance/favicon', { preserveScroll: true });
        }
    }

    return (
        <div className="mb-8 pb-8 border-b border-admin-border">
            <h2 className="text-sm font-semibold text-admin-text mb-3">Favicon</h2>
            <form onSubmit={submit} className="flex items-center gap-4">
                <img
                    src={previewUrl ?? faviconUrl}
                    alt="Current favicon"
                    className="h-12 w-12 rounded-admin-button border border-admin-border object-contain bg-admin-surface p-1"
                />
                <div className="flex-1 min-w-0">
                    <input
                        type="file"
                        accept="image/x-icon,image/png,image/svg+xml,image/webp,image/jpeg,.ico"
                        onChange={(e) => pickFile(e.target.files?.[0] ?? null)}
                        className="block w-full text-sm text-admin-text file:mr-3 file:rounded-admin-button file:border file:border-admin-border-strong file:bg-admin-surface file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-admin-text"
                    />
                    {errors.favicon && <p className="text-xs text-admin-red mt-1">{errors.favicon}</p>}
                    <p className="text-xs text-admin-text3 mt-1">ICO, PNG, SVG, WebP, or JPG — a small square works best, up to 1MB.</p>
                </div>
                <div className="flex items-center gap-2 shrink-0">
                    <AdminButton type="submit" isLoading={processing} disabled={!data.favicon}>
                        Save favicon
                    </AdminButton>
                    {hasCustomFavicon && (
                        <AdminButton type="button" variant="danger" onClick={resetFavicon}>
                            Reset
                        </AdminButton>
                    )}
                </div>
            </form>
        </div>
    );
}

/**
 * The whole site's accent colour — every blue button, active nav item, badge,
 * focus ring, and highlight across the admin panel, the customer portal, AND
 * the public landing page resolves from this one value (resources/css/tokens.css
 * derives the soft/hover/focus tints from it automatically via color-mix()),
 * so this single picker recolours the entire site consistently, not just one
 * area. The landing page's own "Accent" colour row has been removed from the
 * list below for this reason — it would otherwise look editable but do nothing.
 */
function SiteThemeSection({ accent, defaultAccent }: { accent: string; defaultAccent: string }) {
    const confirm = useConfirm();
    const [draft, setDraft] = useState(accent);
    const [busy, setBusy] = useState(false);

    const valid = HEX.test(draft);
    const dirty = draft !== accent;

    function save() {
        setBusy(true);
        router.put('/admin/settings/appearance/admin-theme', { accent: draft }, {
            preserveScroll: true,
            onFinish: () => setBusy(false),
        });
    }

    async function reset() {
        const confirmed = await confirm({
            kind: 'warning',
            title: 'Reset the site theme colour?',
            body: 'Every button, active state, and highlight across the admin panel, customer portal, and landing page goes back to the original blue.',
            confirmLabel: 'Reset',
        });

        if (confirmed) {
            setDraft(defaultAccent);
            router.delete('/admin/settings/appearance/admin-theme', { preserveScroll: true });
        }
    }

    return (
        <div className="mb-8 pb-8 border-b border-admin-border">
            <h2 className="text-sm font-semibold text-admin-text mb-3">Site theme colour</h2>
            <p className="text-xs text-admin-text3 mb-3">
                One colour for the whole site — admin panel, customer portal, and the public landing page.
            </p>
            <div className="flex items-center gap-4 flex-wrap">
                <input
                    type="color"
                    aria-label="Site accent colour"
                    value={valid ? draft : defaultAccent}
                    onChange={(e) => setDraft(e.target.value.toUpperCase())}
                    className="h-9 w-12 shrink-0 cursor-pointer rounded-admin-button border border-admin-border-strong bg-transparent p-0.5"
                />
                <input
                    type="text"
                    aria-label="Site accent colour hex value"
                    value={draft}
                    maxLength={7}
                    onChange={(e) => setDraft(e.target.value)}
                    className={`w-28 border rounded-admin-button px-2 py-1.5 font-mono text-sm text-admin-text outline-none focus:ring-2 focus:ring-admin-focus ${
                        valid ? 'border-admin-border-strong' : 'border-admin-border-danger'
                    }`}
                />
                <span
                    className="inline-flex items-center rounded-admin-button px-4 py-2 text-sm font-semibold text-white"
                    style={{ background: valid ? draft : defaultAccent }}
                >
                    Preview button
                </span>
                <div className="flex items-center gap-2 ml-auto">
                    <AdminButton type="button" onClick={save} disabled={!dirty || !valid || busy}>
                        {busy ? 'Saving…' : 'Save colour'}
                    </AdminButton>
                    <AdminButton type="button" variant="danger" onClick={reset}>
                        Reset
                    </AdminButton>
                </div>
            </div>
            {!valid && <p className="text-xs text-admin-red mt-2">Must be six hex digits, like #1E56E8.</p>}
        </div>
    );
}

/** Settings → Site appearance. Changes the site logo, the admin UI's accent colour, and the landing page's colours; the page itself is unchanged apart from its palette. */
export default function SiteAppearanceIndex({
    palette,
    defaults,
    logoUrl,
    hasCustomLogo,
    faviconUrl,
    hasCustomFavicon,
    adminThemeAccent,
    adminThemeDefault,
}: SiteAppearanceProps) {
    const confirm = useConfirm();
    const [draft, setDraft] = useState<WelcomePalette>(palette);
    const [busy, setBusy] = useState(false);

    const dirty = WELCOME_PALETTE_TOKENS.some((token) => draft[token.key] !== palette[token.key]);
    const invalid = WELCOME_PALETTE_TOKENS.some((token) => !HEX.test(draft[token.key]));

    function setToken(key: keyof WelcomePalette, value: string) {
        setDraft((current) => ({ ...current, [key]: value }));
    }

    function save() {
        setBusy(true);
        router.put('/admin/settings/appearance', { palette: draft }, {
            preserveScroll: true,
            onFinish: () => setBusy(false),
        });
    }

    async function reset() {
        const confirmed = await confirm({
            kind: 'warning',
            title: 'Reset landing page colours?',
            body: 'The landing page goes back to its original colours.',
            confirmLabel: 'Reset',
        });

        if (confirmed) {
            router.delete('/admin/settings/appearance', { preserveScroll: true });
        }
    }

    return (
        <AdminShell>
            <Head title="Site appearance" />

            <AdminPageHead
                title="Site appearance"
                description="The colours of the public landing page. Each colour is one token; changes show in the preview below before you save."
            />

            <SettingsSubNav />

            <SiteLogoSection logoUrl={logoUrl} hasCustomLogo={hasCustomLogo} />

            <SiteFaviconSection faviconUrl={faviconUrl} hasCustomFavicon={hasCustomFavicon} />

            <SiteThemeSection accent={adminThemeAccent} defaultAccent={adminThemeDefault} />

            <div className="grid grid-cols-1 xl:grid-cols-2 gap-8 items-start">
                <div>
                    <h2 className="text-sm font-semibold text-admin-text mb-3">Landing page colours</h2>
                    <p className="text-xs text-admin-text3 mb-3">
                        The landing page's "Accent" colour is now the site theme colour above — set it there.
                    </p>
                    <ul className="flex flex-col gap-3">
                        {WELCOME_PALETTE_TOKENS.filter((token) => token.key !== 'accent').map((token) => {
                            const value = draft[token.key];
                            const valid = HEX.test(value);

                            return (
                                <li key={token.key} className="flex items-center gap-3">
                                    <input
                                        type="color"
                                        aria-label={token.label}
                                        value={valid ? value : defaults[token.key]}
                                        onChange={(e) => setToken(token.key, e.target.value.toUpperCase())}
                                        className="h-9 w-12 shrink-0 cursor-pointer rounded-admin-button border border-admin-border-strong bg-transparent p-0.5"
                                    />
                                    <div className="min-w-0 flex-1">
                                        <div className="text-sm text-admin-text">{token.label}</div>
                                        <div className="text-xs text-admin-text3">Original {defaults[token.key]}</div>
                                    </div>
                                    <input
                                        type="text"
                                        aria-label={`${token.label} hex value`}
                                        value={value}
                                        maxLength={7}
                                        onChange={(e) => setToken(token.key, e.target.value)}
                                        className={`w-28 border rounded-admin-button px-2 py-1.5 font-mono text-sm text-admin-text outline-none focus:ring-2 focus:ring-admin-focus ${
                                            valid ? 'border-admin-border-strong' : 'border-admin-border-danger'
                                        }`}
                                    />
                                </li>
                            );
                        })}
                    </ul>

                    {invalid && <p className="text-xs text-admin-red mt-3">Each colour must be six hex digits, like #2F6FED.</p>}

                    <div className="flex flex-wrap items-center gap-2 mt-6">
                        <AdminButton type="button" onClick={save} disabled={!dirty || invalid || busy}>
                            {busy ? 'Saving…' : 'Save colours'}
                        </AdminButton>
                        <AdminButton type="button" variant="neutral" onClick={() => setDraft(palette)} disabled={!dirty}>
                            Discard changes
                        </AdminButton>
                        <AdminButton type="button" variant="danger" onClick={reset}>
                            Reset to original
                        </AdminButton>
                        <a href="/" target="_blank" rel="noreferrer" className="text-sm font-medium text-admin-blue hover:underline ml-auto">
                            Open landing page ↗
                        </a>
                    </div>
                </div>

                <div>
                    <h2 className="text-sm font-semibold text-admin-text mb-3">Preview</h2>
                    <div
                        className="lp-preview rounded-admin-card overflow-hidden border border-admin-border"
                        style={
                            {
                                ...paletteVars(draft),
                                '--lp-blue': HEX.test(adminThemeAccent) ? adminThemeAccent : adminThemeDefault,
                            } as CSSProperties
                        }
                    >
                        <div style={{ background: `linear-gradient(to bottom, var(--lp-page-start), var(--lp-page-mid), var(--lp-page-end))` }} className="p-6">
                            <p className="text-xs font-semibold tracking-wide" style={{ color: 'var(--lp-text2)' }}>
                                AFPROSPOS
                            </p>
                            <h3 className="text-2xl font-bold mt-1" style={{ color: 'var(--lp-text)' }}>
                                Your phone, sorted.
                            </h3>
                            <p className="text-sm mt-2" style={{ color: 'var(--lp-text2)' }}>
                                Buy, repair and collect in one place.
                            </p>
                            <span
                                className="inline-block mt-4 rounded-full px-4 py-2 text-sm font-semibold"
                                style={{ background: 'var(--lp-blue)', color: 'var(--lp-on-accent)' }}
                            >
                                Get started
                            </span>
                        </div>
                        <div className="grid grid-cols-3 gap-2 p-3" style={{ background: 'var(--lp-surface)' }}>
                            <div className="rounded-xl p-3 text-xs font-semibold" style={{ background: 'var(--lp-lime)', color: 'var(--lp-on-lime)' }}>
                                Repairs
                            </div>
                            <div className="rounded-xl p-3 text-xs font-semibold" style={{ background: 'var(--lp-navy)', color: 'var(--lp-on-accent)' }}>
                                Sales
                            </div>
                            <div className="rounded-xl p-3 text-xs font-semibold" style={{ background: 'var(--lp-blue)', color: 'var(--lp-on-accent)' }}>
                                Warranty
                            </div>
                        </div>
                        <div className="px-4 py-3 text-xs" style={{ background: 'var(--lp-panel)', color: 'var(--lp-text2)', borderTop: '1px solid var(--lp-border)' }}>
                            Footer and light panels
                        </div>
                    </div>
                </div>
            </div>
        </AdminShell>
    );
}
