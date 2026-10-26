import React, { forwardRef, isValidElement, KeyboardEvent, ReactElement, ReactNode, useEffect, useMemo, useRef, useState } from 'react';
import FormField from '../../Forms/FormField';
import FormErrorMessage from '../../Forms/FormErrorMessage';
import FormSuccessMessage from '../../Forms/FormSuccessMessage';
import Icon from '../../Icons/Icon';

interface AdminSelectProps {
    /** Omit for a compact inline filter that only needs an `aria-label`, not a visible label row. */
    label?: string;
    error?: string;
    success?: string;
    value?: string | number;
    /** Mirrors a native <select>'s onChange shape (`e.target.value`) so every existing call site works unchanged. */
    onChange?: (event: { target: { value: string } }) => void;
    /** Same `<option value="...">label</option>` children a native <select> takes — parsed into the dropdown's option list. */
    children?: ReactNode;
    disabled?: boolean;
    required?: boolean;
    className?: string;
    /** Overrides FormField's default `flex flex-col mb-5 relative` wrapper — e.g. an inline filter bar or a select sitting beside a button in one row wants no bottom margin. */
    containerClassName?: string;
    id?: string;
    name?: string;
    'aria-label'?: string;
}

type OptionEntry = { value: string; label: ReactNode; disabled: boolean };

/** Reads the same `<option>` children a native select takes, so call sites keep writing `{items.map(i => <option value={i.id}>{i.name}</option>)}` unchanged. */
function collectOptions(children: ReactNode): OptionEntry[] {
    const options: OptionEntry[] = [];

    React.Children.forEach(children, (child) => {
        if (!isValidElement(child)) return;
        const option = child as ReactElement<{ value?: string | number; children?: ReactNode; disabled?: boolean }>;
        options.push({
            value: String(option.props.value ?? ''),
            label: option.props.children,
            disabled: Boolean(option.props.disabled),
        });
    });

    return options;
}

/**
 * A native `<select>`'s own dropdown popup is rendered by the browser/OS,
 * not this app — CSS cannot restyle it (no rounded corners, no custom
 * colors, no control over its width), and on a real mobile device it's
 * often an OS picker sheet that's even more locked down. This is a
 * custom-built listbox instead: a styled trigger button + an absolutely
 * positioned, fully-themed options panel, with the same external API
 * (`value`/`onChange(e.target.value)`/`<option>` children) a native
 * select has, so every existing call site needed no changes.
 */
const AdminSelect = forwardRef<HTMLButtonElement, AdminSelectProps>(
    ({ label, error, success, value, onChange, children, disabled = false, required, className = '', containerClassName, id: idProp, name, ...rest }, ref) => {
        const [open, setOpen] = useState(false);
        const [highlighted, setHighlighted] = useState(0);
        const containerRef = useRef<HTMLDivElement>(null);
        const listRef = useRef<HTMLUListElement>(null);

        const options = useMemo(() => collectOptions(children), [children]);
        const selectedIndex = options.findIndex((option) => option.value === String(value ?? ''));
        const selected = selectedIndex >= 0 ? options[selectedIndex] : null;

        useEffect(() => {
            if (!open) return;

            setHighlighted(selectedIndex >= 0 ? selectedIndex : 0);
            listRef.current?.focus();

            const handleClickOutside = (e: MouseEvent) => {
                if (containerRef.current && !containerRef.current.contains(e.target as Node)) setOpen(false);
            };
            document.addEventListener('mousedown', handleClickOutside);

            return () => document.removeEventListener('mousedown', handleClickOutside);
            // eslint-disable-next-line react-hooks/exhaustive-deps
        }, [open]);

        function choose(index: number) {
            const option = options[index];
            if (!option || option.disabled) return;
            onChange?.({ target: { value: option.value } });
            setOpen(false);
        }

        function handleTriggerKeyDown(e: KeyboardEvent<HTMLButtonElement>) {
            if (disabled) return;
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                setOpen(true);
            }
        }

        function handleListKeyDown(e: KeyboardEvent<HTMLUListElement>) {
            if (e.key === 'Escape') {
                e.preventDefault();
                setOpen(false);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                setHighlighted((current) => Math.min(options.length - 1, current + 1));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setHighlighted((current) => Math.max(0, current - 1));
            } else if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                choose(highlighted);
            } else if (e.key === 'Tab') {
                setOpen(false);
            }
        }

        let borderClass = 'border-admin-border-strong hover:border-admin-blue focus:border-admin-blue focus:ring-admin-focus focus:ring-2';
        if (error) {
            borderClass = 'border-admin-red focus:border-admin-red focus:ring-admin-red-soft focus:ring-2';
        } else if (success) {
            borderClass = 'border-admin-green focus:border-admin-green focus:ring-admin-green-soft focus:ring-2';
        }

        return (
            <FormField
                label={label}
                labelClassName="text-xs font-semibold text-admin-text mb-[5px]"
                {...(containerClassName !== undefined ? { containerClassName } : {})}
            >
                {(id) => (
                    <>
                        <div className="relative" ref={containerRef}>
                            <button
                                ref={ref}
                                id={idProp ?? id}
                                type="button"
                                name={name}
                                disabled={disabled}
                                aria-required={required}
                                onClick={() => !disabled && setOpen((current) => !current)}
                                onKeyDown={handleTriggerKeyDown}
                                aria-haspopup="listbox"
                                aria-expanded={open}
                                className={`
                                    w-full bg-white border rounded-admin-button pl-admin-field-x pr-8 py-admin-field-y
                                    font-admin-base text-admin-field text-left truncate
                                    transition-all duration-200 outline-none
                                    disabled:bg-admin-hover disabled:text-admin-text3 disabled:cursor-not-allowed
                                    ${selected ? 'text-admin-text' : 'text-admin-text3'}
                                    ${open ? 'border-admin-blue ring-2 ring-admin-focus' : borderClass}
                                    ${className}
                                `}
                                {...rest}
                            >
                                {selected ? selected.label : 'Select…'}
                            </button>
                            <Icon
                                name="chevron-down"
                                className={`pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-admin-text3 transition-transform duration-150 ${open ? 'rotate-180' : ''}`}
                            />

                            {open && (
                                <ul
                                    ref={listRef}
                                    role="listbox"
                                    tabIndex={-1}
                                    onKeyDown={handleListKeyDown}
                                    className="absolute z-30 mt-1 w-full max-h-64 overflow-y-auto bg-white border border-admin-border rounded-admin-card shadow-admin-modal py-1 outline-none [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                                >
                                    {options.map((option, index) => (
                                        <li
                                            key={option.value}
                                            role="option"
                                            aria-selected={index === selectedIndex}
                                            aria-disabled={option.disabled}
                                            onClick={() => choose(index)}
                                            onMouseEnter={() => setHighlighted(index)}
                                            className={`
                                                px-3 py-2.5 text-sm truncate
                                                ${option.disabled ? 'text-admin-text3 cursor-not-allowed' : 'text-admin-text cursor-pointer'}
                                                ${index === selectedIndex ? 'bg-admin-blue text-white' : index === highlighted ? 'bg-admin-blue-soft' : ''}
                                            `}
                                        >
                                            {option.label}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                        {error && <FormErrorMessage className="mt-1 text-xs">{error}</FormErrorMessage>}
                        {!error && success && <FormSuccessMessage className="mt-1 text-xs">{success}</FormSuccessMessage>}
                    </>
                )}
            </FormField>
        );
    }
);

AdminSelect.displayName = 'AdminSelect';
export default AdminSelect;
