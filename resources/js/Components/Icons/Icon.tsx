import React from 'react';
import 'bootstrap-icons/font/bootstrap-icons.css';

interface IconProps extends React.HTMLAttributes<HTMLElement> {
    name: string; // The bootstrap icon name, e.g., 'house', 'gear'
}

export default function Icon({ name, className = '', ...props }: IconProps) {
    return (
        <i className={`bi bi-${name} ${className}`} aria-hidden="true" {...props} />
    );
}
