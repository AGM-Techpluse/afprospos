export function formatNaira(minor: number): string {
    return `₦${(minor / 100).toLocaleString('en-NG', { minimumFractionDigits: 0 })}`;
}
