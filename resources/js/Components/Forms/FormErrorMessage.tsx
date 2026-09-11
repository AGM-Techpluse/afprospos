import React, { ReactNode } from 'react';
import Icon from '../Icons/Icon';

interface FormErrorMessageProps {
    children: ReactNode;
    className?: string;
}

/** Theme-agnostic error row (UI/UX §10.4) — consumes --ui-danger so Admin and Customer both get the right color automatically via the nearest [data-theme] ancestor. */
export default function FormErrorMessage({ children, className = '' }: FormErrorMessageProps) {
    return (
        <div className={`flex items-center text-ui-danger ${className}`}>
            <Icon name="exclamation-circle" className="mr-1" />
            {children}
        </div>
    );
}
