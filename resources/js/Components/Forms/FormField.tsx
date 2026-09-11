import React, { ReactNode, useId } from 'react';

interface FormFieldProps {
    label: string;
    labelClassName: string;
    containerClassName?: string;
    htmlFor?: string;
    children: (id: string) => ReactNode;
}

/**
 * Theme-agnostic label + control layout (UI/UX §10.2/§10.3 share this
 * structure — label above control — even though field fill/border styling
 * differs per theme, which stays owned by AdminInput/CustomerInput).
 */
export default function FormField({
    label,
    labelClassName,
    containerClassName = 'flex flex-col mb-5 relative',
    htmlFor,
    children,
}: FormFieldProps) {
    const generatedId = useId();
    const id = htmlFor ?? generatedId;

    return (
        <div className={containerClassName}>
            <label htmlFor={id} className={labelClassName}>
                {label}
            </label>
            {children(id)}
        </div>
    );
}
