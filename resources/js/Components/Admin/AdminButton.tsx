import React, { ButtonHTMLAttributes } from 'react';
import Icon from '../Icons/Icon';

interface AdminButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: 'primary' | 'neutral' | 'warning' | 'danger' | 'danger-solid';
    isLoading?: boolean;
    loadingText?: string;
}

export default function AdminButton({
    variant = 'primary',
    isLoading = false,
    loadingText = 'Please wait...',
    className = '',
    children,
    disabled,
    ...props
}: AdminButtonProps) {
    let baseClass = 'rounded-admin-button text-admin-button font-semibold px-admin-btn-x py-admin-btn-y transition-transform duration-120 active:scale-98 flex items-center justify-center';
    let variantClass = '';

    switch (variant) {
        case 'primary':
            variantClass = 'bg-admin-blue text-white hover:bg-blue-700';
            break;
        case 'neutral':
            variantClass = 'bg-white border border-admin-border-strong text-admin-text hover:bg-admin-hover';
            break;
        case 'warning':
            variantClass = 'bg-admin-yellow-soft text-admin-yellow-text border border-admin-border-warning hover:bg-yellow-200';
            break;
        case 'danger':
            variantClass = 'bg-admin-red-soft text-admin-red border border-admin-border-danger hover:bg-red-100';
            break;
        case 'danger-solid':
            variantClass = 'bg-admin-red text-white hover:bg-red-700';
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
