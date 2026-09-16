import PatternLockPad from './PatternLockPad';

export type DeviceLockType = 'none' | 'code' | 'pattern';

interface DeviceLockInputProps {
    lockType: DeviceLockType;
    lockValue: string;
    onChange: (lockType: DeviceLockType, lockValue: string) => void;
    error?: string;
    disabled?: boolean;
}

const inputClass =
    'w-full border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus';

/** Captures how to get into the customer's device for diagnosis — a passcode, a swipe pattern, or neither. Encrypted at rest server-side and only ever shown back to the assigned technician or the Shop Owner (RepairJobDetailQuery). */
export default function DeviceLockInput({ lockType, lockValue, onChange, error, disabled = false }: DeviceLockInputProps) {
    const patternDots = lockType === 'pattern' && lockValue ? lockValue.split('-').map(Number) : [];

    return (
        <div className="mb-5">
            <label className="text-xs font-semibold text-admin-text mb-[5px] block">Device lock (optional)</label>
            <div className="flex gap-1.5 mb-2">
                {(['none', 'code', 'pattern'] as const).map((type) => (
                    <button
                        key={type}
                        type="button"
                        disabled={disabled}
                        onClick={() => onChange(type, '')}
                        className={`text-xs font-semibold rounded-admin-button px-2.5 py-1.5 border transition-colors ${
                            lockType === type
                                ? 'bg-admin-blue text-white border-admin-blue'
                                : 'bg-white text-admin-text2 border-admin-border-strong hover:border-admin-blue'
                        }`}
                    >
                        {type === 'none' ? 'None' : type === 'code' ? 'Passcode' : 'Pattern'}
                    </button>
                ))}
            </div>

            {lockType === 'code' && (
                <input
                    type="text"
                    value={lockValue}
                    onChange={(e) => onChange('code', e.target.value)}
                    disabled={disabled}
                    placeholder="e.g. 1234"
                    className={inputClass}
                />
            )}

            {lockType === 'pattern' && (
                <div>
                    <PatternLockPad
                        value={patternDots}
                        onChange={(dots) => onChange('pattern', dots.join('-'))}
                        disabled={disabled}
                    />
                    <div className="flex items-center gap-2 mt-1.5">
                        <p className="text-xs text-admin-text3">Drag across the dots to draw the pattern.</p>
                        {patternDots.length > 0 && (
                            <button type="button" onClick={() => onChange('pattern', '')} className="text-xs font-semibold text-admin-blue">
                                Clear
                            </button>
                        )}
                    </div>
                </div>
            )}

            {error && <p className="text-xs text-ui-danger mt-1">{error}</p>}
        </div>
    );
}
