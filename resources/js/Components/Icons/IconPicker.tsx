import { useEffect, useMemo, useRef, useState } from 'react';
import Icon from './Icon';
import { BOOTSTRAP_ICON_NAMES } from './iconNames';

/** A reasonable starting grid before the staff member types anything — common device/electronics icons, not an alphabetical dump of 2000+ names. */
const SUGGESTED_ICONS = [
    'phone', 'laptop', 'tablet', 'smartwatch', 'joystick', 'airplane', 'camera', 'tv',
    'speaker', 'headphones', 'router', 'printer', 'keyboard', 'mouse2', 'hdd', 'cpu',
    'usb-drive', 'battery-charging', 'webcam', 'projector', 'display', 'pc-display',
    'calculator', 'device-ssd', 'ethernet', 'wifi', 'bluetooth', 'camera-reels',
];

interface IconPickerProps {
    value: string;
    onChange: (name: string) => void;
    /** Small inline trigger (just the glyph) instead of the full "glyph + name" button — for tight spaces like an inline edit form row. */
    compact?: boolean;
}

/**
 * Staff shouldn't need to know Bootstrap Icons' exact slug names to set a
 * device type's icon — this replaces the old bare text input with a
 * searchable, visual picker over the same full icon set Icon.tsx renders
 * (`node_modules/bootstrap-icons/font/bootstrap-icons.json`, 2078 names).
 */
export default function IconPicker({ value, onChange, compact = false }: IconPickerProps) {
    const [open, setOpen] = useState(false);
    const [term, setTerm] = useState('');
    const containerRef = useRef<HTMLDivElement>(null);
    const searchRef = useRef<HTMLInputElement>(null);

    const results = useMemo(() => {
        const query = term.trim().toLowerCase();

        if (query === '') {
            return SUGGESTED_ICONS;
        }

        return BOOTSTRAP_ICON_NAMES.filter((name) => name.includes(query)).slice(0, 120);
    }, [term]);

    useEffect(() => {
        if (open) {
            searchRef.current?.focus();
        }
    }, [open]);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    function select(name: string) {
        onChange(name);
        setTerm('');
        setOpen(false);
    }

    return (
        <div className="relative" ref={containerRef}>
            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                className={
                    compact
                        ? 'w-9 h-9 rounded-admin-button border border-admin-border-strong bg-white flex items-center justify-center text-admin-text hover:border-admin-blue shrink-0'
                        : 'flex items-center gap-2 border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs bg-white hover:border-admin-blue w-full'
                }
                aria-label="Choose icon"
            >
                <Icon name={value || 'question-circle'} className={compact ? 'text-base' : 'text-sm'} />
                {!compact && <span className="text-admin-text2 truncate">{value || 'Choose an icon…'}</span>}
            </button>

            {open && (
                <div className="absolute z-20 mt-1 w-72 bg-white border border-admin-border rounded-admin-card shadow-admin-modal p-2">
                    <input
                        ref={searchRef}
                        type="text"
                        value={term}
                        onChange={(e) => setTerm(e.target.value)}
                        placeholder="Search icons…"
                        className="w-full border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs mb-2 outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                    />
                    {results.length === 0 ? (
                        <p className="text-xs text-admin-text3 text-center py-3">No icons match "{term}".</p>
                    ) : (
                        <div className="grid grid-cols-7 gap-1 max-h-56 overflow-y-auto">
                            {results.map((name) => (
                                <button
                                    key={name}
                                    type="button"
                                    onClick={() => select(name)}
                                    title={name}
                                    className={`w-9 h-9 rounded-admin-button flex items-center justify-center text-base transition-colors ${
                                        name === value ? 'bg-admin-blue text-white' : 'text-admin-text2 hover:bg-admin-hover hover:text-admin-blue'
                                    }`}
                                >
                                    <Icon name={name} />
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
