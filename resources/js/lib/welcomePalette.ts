import type { CSSProperties } from 'react';

/**
 * The landing page's colour tokens. Every colour on the Welcome page is one of
 * these, so the admin's Site appearance settings can recolour the whole page.
 * The defaults match the page's original design and are also the server fallback.
 */
export const WELCOME_PALETTE_TOKENS = [
    { key: 'page_start', label: 'Hero background (top)', cssVar: '--lp-page-start', default: '#CFE3FF' },
    { key: 'page_mid', label: 'Hero background (middle)', cssVar: '--lp-page-mid', default: '#EAF3FF' },
    { key: 'page_end', label: 'Hero background (bottom)', cssVar: '--lp-page-end', default: '#FFFFFF' },
    { key: 'text', label: 'Main text', cssVar: '--lp-text', default: '#1B1F27' },
    { key: 'text_muted', label: 'Secondary text', cssVar: '--lp-text2', default: '#636B78' },
    { key: 'accent', label: 'Accent (buttons, highlights)', cssVar: '--lp-blue', default: '#2F6FED' },
    { key: 'surface', label: 'Cards and footer', cssVar: '--lp-surface', default: '#FFFFFF' },
    { key: 'on_accent', label: 'Text on accent and dark areas', cssVar: '--lp-on-accent', default: '#FFFFFF' },
    { key: 'panel', label: 'Light panels', cssVar: '--lp-panel', default: '#E8EDF3' },
    { key: 'border', label: 'Dividers and borders', cssVar: '--lp-border', default: '#EEF0F3' },
    { key: 'navy', label: 'Dark service card', cssVar: '--lp-navy', default: '#10192E' },
    { key: 'lime', label: 'Lime service card', cssVar: '--lp-lime', default: '#A0ED25' },
    { key: 'on_lime', label: 'Text on lime card', cssVar: '--lp-on-lime', default: '#14200A' },
    { key: 'ink', label: 'Photo overlay and black areas', cssVar: '--lp-ink', default: '#000000' },
] as const;

export type WelcomePalette = Record<(typeof WELCOME_PALETTE_TOKENS)[number]['key'], string>;

/** Turns a palette into the CSS custom properties the page's styles read. */
export function paletteVars(palette: WelcomePalette): CSSProperties {
    const vars: Record<string, string> = {};

    for (const token of WELCOME_PALETTE_TOKENS) {
        vars[token.cssVar] = palette[token.key];
    }

    return vars as CSSProperties;
}
