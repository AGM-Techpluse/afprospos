import { useLayoutEffect } from 'react';

/**
 * Overriding --a-blue/--c-blue on a descendant div (e.g. AdminShell's root)
 * does NOT work: Tailwind's @theme block declares --color-admin-blue etc.
 * only on :root, so that declaration's var(--a-blue) substitution happens
 * ONCE at :root and then just inherits unchanged down the tree — it never
 * re-resolves against an override set further down. Setting the accent
 * directly on document.documentElement (the real :root element) puts the
 * override on the SAME element Tailwind's theme tokens are declared on, so
 * every derived token (soft/hover/focus, and --ui-accent/--ui-info which
 * read --a-blue under [data-theme="admin"]) recomputes correctly.
 */
export function useSiteThemeAccent(accent: string | undefined): void {
    useLayoutEffect(() => {
        if (!accent) return;

        document.documentElement.style.setProperty('--a-blue', accent);
        document.documentElement.style.setProperty('--c-blue', accent);
    }, [accent]);
}
