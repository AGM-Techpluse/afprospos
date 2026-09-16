import { PointerEvent as ReactPointerEvent, useRef, useState } from 'react';

const SIZE = 240;
const MARGIN = 40;
const STEP = (SIZE - 2 * MARGIN) / 2;
const DOT_RADIUS = 8;
const HIT_RADIUS = 28;

// Dot numbers 1-9 laid out left-to-right, top-to-bottom — "swipe the top
// row left to right" is 1,2,3; "diagonal top-left to bottom-right" is 1,5,9.
const POSITIONS: Record<number, { x: number; y: number; row: number; col: number }> = {};
for (let n = 1; n <= 9; n++) {
    const row = Math.floor((n - 1) / 3);
    const col = (n - 1) % 3;
    POSITIONS[n] = { x: MARGIN + col * STEP, y: MARGIN + row * STEP, row, col };
}

/** If B is reached from A by crossing straight through another dot (e.g. 1 -> 3 passes through 2), that dot is inserted first even if the user didn't pause on it — matches the standard Android lock-pattern behavior. */
function passthroughDot(a: number, b: number, visited: Set<number>): number | null {
    const posA = POSITIONS[a];
    const posB = POSITIONS[b];
    if ((posA.row + posB.row) % 2 !== 0 || (posA.col + posB.col) % 2 !== 0) return null;

    const midRow = (posA.row + posB.row) / 2;
    const midCol = (posA.col + posB.col) / 2;
    const candidate = Object.entries(POSITIONS).find(([, pos]) => pos.row === midRow && pos.col === midCol);
    if (!candidate) return null;

    const dot = Number(candidate[0]);
    return dot !== a && dot !== b && !visited.has(dot) ? dot : null;
}

interface PatternLockPadProps {
    value: number[];
    onChange: (sequence: number[]) => void;
    readOnly?: boolean;
    disabled?: boolean;
}

/** A 3x3 swipe-pattern lock, the same interaction as Android's own lock screen — drag across dots to build a sequence; used to capture (or redraw, in read-only mode) a customer's device unlock pattern. */
export default function PatternLockPad({ value, onChange, readOnly = false, disabled = false }: PatternLockPadProps) {
    const [dragging, setDragging] = useState(false);
    const [cursor, setCursor] = useState<{ x: number; y: number } | null>(null);
    const svgRef = useRef<SVGSVGElement>(null);

    function pointToSvg(clientX: number, clientY: number): { x: number; y: number } {
        const rect = svgRef.current!.getBoundingClientRect();
        return { x: ((clientX - rect.left) / rect.width) * SIZE, y: ((clientY - rect.top) / rect.height) * SIZE };
    }

    function dotAt(x: number, y: number): number | null {
        for (const [n, pos] of Object.entries(POSITIONS)) {
            if (Math.hypot(pos.x - x, pos.y - y) <= HIT_RADIUS) return Number(n);
        }
        return null;
    }

    function start(e: ReactPointerEvent<SVGSVGElement>) {
        if (readOnly || disabled) return;
        const point = pointToSvg(e.clientX, e.clientY);
        const dot = dotAt(point.x, point.y);
        setDragging(true);
        setCursor(point);
        onChange(dot !== null ? [dot] : []);
    }

    function move(e: ReactPointerEvent<SVGSVGElement>) {
        if (!dragging) return;
        const point = pointToSvg(e.clientX, e.clientY);
        setCursor(point);

        const dot = dotAt(point.x, point.y);
        if (dot === null || value.includes(dot)) return;

        const next = [...value];
        if (next.length > 0) {
            const through = passthroughDot(next[next.length - 1], dot, new Set(next));
            if (through !== null) next.push(through);
        }
        next.push(dot);
        onChange(next);
    }

    function end() {
        setDragging(false);
        setCursor(null);
    }

    const points = value.map((n) => POSITIONS[n]);
    const lastPoint = points[points.length - 1];

    return (
        <svg
            ref={svgRef}
            viewBox={`0 0 ${SIZE} ${SIZE}`}
            width={SIZE}
            height={SIZE}
            className={`touch-none select-none ${readOnly || disabled ? '' : 'cursor-pointer'}`}
            onPointerDown={start}
            onPointerMove={move}
            onPointerUp={end}
            onPointerLeave={end}
        >
            <rect x={0} y={0} width={SIZE} height={SIZE} rx={12} className="fill-admin-bg" />

            {points.length > 1 && (
                <polyline
                    points={points.map((p) => `${p.x},${p.y}`).join(' ')}
                    fill="none"
                    className="stroke-admin-blue"
                    strokeWidth={3}
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            )}
            {dragging && lastPoint && cursor && (
                <line x1={lastPoint.x} y1={lastPoint.y} x2={cursor.x} y2={cursor.y} className="stroke-admin-blue" strokeWidth={3} strokeLinecap="round" />
            )}

            {Object.entries(POSITIONS).map(([n, pos]) => {
                const active = value.includes(Number(n));
                return (
                    <circle
                        key={n}
                        cx={pos.x}
                        cy={pos.y}
                        r={active ? DOT_RADIUS + 3 : DOT_RADIUS}
                        className={active ? 'fill-admin-blue' : 'fill-admin-border-strong'}
                    />
                );
            })}
        </svg>
    );
}
