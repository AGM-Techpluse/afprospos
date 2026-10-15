// Matches the existing Dashboard quick-action color convention (Repairs =
// blue, Sales = green) rather than inventing a new mapping. Validated as a
// colorblind-safe categorical pair (dataviz skill: CVD ΔE 31.7, normal-vision
// ΔE 35.4, both well above the ΔE >= 8 floor). A third stream (e.g. trade-ins)
// needs its own hue re-validated against this pair before it's added here —
// the fixed order below must never just cycle in a new color.
export const REVENUE_STREAM_COLORS: Record<string, string> = {
    repair_job: '#1E56E8',
    sales_checkout: '#16A34A',
};

export const REVENUE_STREAM_FALLBACK_COLOR = '#98A2B3';
