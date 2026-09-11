import React, { ReactNode } from 'react';
import Icon from '../Icons/Icon';

interface FormSuccessMessageProps {
    children: ReactNode;
    className?: string;
}

/** Theme-agnostic success row (UI/UX §10.4) — consumes --ui-success so Admin and Customer both get the right color automatically via the nearest [data-theme] ancestor. */
export default function FormSuccessMessage({ children, className = '' }: FormSuccessMessageProps) {
    return (
        <div className={`flex items-center text-ui-success ${className}`}>
            <Icon name="check-circle" className="mr-1" />
            {children}
        </div>
    );
}
