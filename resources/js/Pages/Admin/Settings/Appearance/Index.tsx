import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import SettingsSubNav from '../../../../Components/Admin/SettingsSubNav';
import AdminButton from '../../../../Components/Admin/AdminButton';
import { useConfirm } from '../../../../hooks/useConfirm';
import { paletteVars, WELCOME_PALETTE_TOKENS, WelcomePalette } from '../../../../lib/welcomePalette';

interface SiteAppearanceProps {
    palette: WelcomePalette;
    defaults: WelcomePalette;
}

const HEX = /^#[0-9A-Fa-f]{6}$/;

/** Settings → Site appearance. Changes the landing page's colours; the page itself is unchanged apart from its palette. */
export default function SiteAppearanceIndex({ palette, defaults }: SiteAppearanceProps) {
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

            <div className="grid grid-cols-1 xl:grid-cols-2 gap-8 items-start">
                <div>
                    <h2 className="text-sm font-semibold text-admin-text mb-3">Landing page colours</h2>
                    <ul className="flex flex-col gap-3">
                        {WELCOME_PALETTE_TOKENS.map((token) => {
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
                    <div className="lp-preview rounded-admin-card overflow-hidden border border-admin-border" style={paletteVars(draft)}>
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
