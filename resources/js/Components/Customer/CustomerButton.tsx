import React, { ButtonHTMLAttributes } from 'react';
import Icon from '../Icons/Icon';

interface CustomerButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: 'primary' | 'neutral' | 'warning' | 'danger';
    isLoading?: boolean;
    loadingText?: string;
}

export default function CustomerButton({
    variant = 'primary',
    isLoading = false,
    loadingText = 'Please wait...',
    className = '',
    children,
    disabled,
    ...props
}: CustomerButtonProps) {
    let baseClass = 'rounded-customer-pill text-customer-button font-bold px-customer-btn-x py-customer-btn-y transition-transform duration-120 active:scale-98 flex items-center justify-center';
    let variantClass = '';

    switch (variant) {
        case 'primary':
            variantClass = 'bg-customer-blue text-white hover:bg-blue-700'; // Need actual hover color if defined
            break;
        case 'neutral':
            variantClass = 'bg-customer-divider text-customer-text hover:bg-gray-200';
            break;
        case 'warning':
            variantClass = 'bg-customer-yellow-soft text-customer-text hover:bg-yellow-200';
            break;
        case 'danger':
            variantClass = 'bg-customer-red-soft text-customer-red hover:bg-red-200';
            break;
    }

    const isDisabled = disabled || isLoading;
    if (isDisabled) {
        baseClass += ' opacity-60 cursor-not-allowed active:scale-100';
    }

    return (
        <button
            className={`${baseClass} ${variantClass} ${className}`}
            disabled={isDisabled}
            {...props}
        >
            {isLoading ? (
                <>
                    <Icon name="arrow-clockwise" className="animate-spin mr-2" />
                    {loadingText}
                </>
            ) : (
                children
            )}
        </button>
    );
}
