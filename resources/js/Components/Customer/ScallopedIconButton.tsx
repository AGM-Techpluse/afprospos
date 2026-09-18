import Icon from '../Icons/Icon';

interface ScallopedIconButtonProps {
    icon: string;
    onClick: () => void;
    ariaLabel: string;
    size?: number;
    className?: string;
}

const PETAL_COUNT = 7;
const CENTER = 50;
const CENTER_RADIUS = 26;
const PETAL_RADIUS = 20;
// RING_DISTANCE + PETAL_RADIUS must stay <= 50 (the viewBox's own radius) —
// it didn't before (34 + 22 = 56), which silently clipped the outer edge of
// every petal at the SVG's own boundary and read as a badly-cropped square
// behind the flower instead of a clean scalloped edge.
const RING_DISTANCE = 26;

/** Precomputed once at module load — the flower silhouette never changes, no reason to recompute trig every render. */
const PETALS = Array.from({ length: PETAL_COUNT }, (_, i) => {
    const angle = (i / PETAL_COUNT) * 2 * Math.PI - Math.PI / 2;
    return { x: CENTER + RING_DISTANCE * Math.cos(angle), y: CENTER + RING_DISTANCE * Math.sin(angle) };
});

/**
 * The scalloped "flower" primary-action button (documentation/UI-ref
 * piggyvest-UI-1.jpeg) — a ring of same-color circles overlapping a
 * center circle, which visually merges into one flower silhouette
 * since nothing has a stroke, just fill. Used for both the desktop FAB
 * (CustomerFab) and the mobile taskbar's center action so the two stay
 * visually identical.
 */
export default function ScallopedIconButton({ icon, onClick, ariaLabel, size = 56, className = '' }: ScallopedIconButtonProps) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={ariaLabel}
            // No shadow class here on purpose — box-shadow follows the
            // button's own (square) box, not its SVG content, so any shadow
            // rendered as a visible square card behind the scalloped shape.
            // Fully transparent background/shadow means only the flower
            // itself is visible, whatever shape it is.
            className={`relative inline-flex items-center justify-center border-0 p-0 bg-transparent ${className}`}
            style={{ width: size, height: size }}
        >
            <svg viewBox="0 0 100 100" className="absolute inset-0 w-full h-full text-customer-blue fill-current" aria-hidden="true">
                <circle cx={CENTER} cy={CENTER} r={CENTER_RADIUS} />
                {PETALS.map((petal, i) => (
                    <circle key={i} cx={petal.x} cy={petal.y} r={PETAL_RADIUS} />
                ))}
            </svg>
            <Icon name={icon} className="relative text-white text-lg" />
        </button>
    );
}
