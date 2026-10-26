import React, { ButtonHTMLAttributes } from 'react';
import Icon from '../Icons/Icon';

interface AdminButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: 'primary' | 'neutral' | 'warning' | 'danger' | 'danger-solid' | 'dark-ghost';
    /** 'sm' shrinks padding/text for a tight container (e.g. inside a dense card) — same shape/radius/motion as 'md', just smaller. */
    size?: 'md' | 'sm';
    isLoading?: boolean;
    loadingText?: string;
}

export default function AdminButton({
    variant = 'primary',
    size = 'md',
    isLoading = false,
    loadingText = 'Please wait...',
    className = '',
    children,
    disabled,
    ...props
}: AdminButtonProps) {
    let baseClass = `rounded-admin-button font-semibold transition-transform duration-120 active:scale-95 flex items-center justify-center ${
        size === 'sm' ? 'text-xs px-2.5 py-1.5 gap-1' : 'text-admin-button px-admin-btn-x py-admin-btn-y'
    }`;
    let variantClass = '';

    switch (variant) {
        case 'primary':
            variantClass = 'bg-admin-blue text-white hover:bg-blue-700';
            break;
        case 'neutral':
            variantClass = 'bg-white border border-admin-border-strong text-admin-text hover:bg-admin-hover hover:border-admin-blue';
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
        case 'dark-ghost':
            // No text color here on purpose — this variant is used on dark surfaces (e.g. the Inventory health card) where the caller's own semantic color (blue/red) is passed via className instead of being baked in, since the two callers need different colors on the same bg/border treatment.
            variantClass = 'bg-white/10 border border-white/15 hover:bg-white/15';
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
