import React, { InputHTMLAttributes, forwardRef, useState } from 'react';
import Icon from '../../Shared/Icon';

interface AdminInputProps extends InputHTMLAttributes<HTMLInputElement> {
    label: string;
    error?: string;
    success?: string;
}

const AdminInput = forwardRef<HTMLInputElement, AdminInputProps>(
    ({ label, error, success, className = '', onBlur, onChange, ...props }, ref) => {
        const [internalError, setInternalError] = useState<string>('');

        const handleBlur = (e: React.FocusEvent<HTMLInputElement>) => {
            if (!e.target.validity.valid) {
                if (e.target.validity.valueMissing) {
                    setInternalError('This field is required.');
                } else if (e.target.validity.typeMismatch && props.type === 'email') {
                    setInternalError('Please enter a valid email address.');
                } else {
                    setInternalError('Invalid format.');
                }
            } else {
                setInternalError('');
            }
            if (onBlur) onBlur(e);
        };

        const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
            if (internalError) {
                setInternalError('');
            }
            if (onChange) onChange(e);
        };

        const displayError = error || internalError;

        let containerClass = 'flex flex-col mb-5 relative';
        let inputBorderClass = 'border-admin-border2 focus:border-admin-blue focus:ring-admin-focus focus:ring-2';
        let messageClass = '';
        let messageIcon = null;

        if (displayError) {
            inputBorderClass = 'border-admin-red focus:border-admin-red focus:ring-admin-red-soft focus:ring-2';
            messageClass = 'text-admin-red mt-1 text-xs flex items-center';
            messageIcon = <Icon name="exclamation-circle" className="mr-1" />;
        } else if (success) {
            inputBorderClass = 'border-admin-green focus:border-admin-green focus:ring-admin-green-soft focus:ring-2';
            messageClass = 'text-admin-green mt-1 text-xs flex items-center';
            messageIcon = <Icon name="check-circle" className="mr-1" />;
        }

        return (
            <div className={containerClass}>
                <label className="text-sm font-semibold text-admin-text mb-1">
                    {label}
                </label>
                <input
                    ref={ref}
                    onBlur={handleBlur}
                    onChange={handleChange}
                    className={`
                        bg-white border rounded-admin-button px-3 py-2
                        font-admin-base text-sm text-admin-text placeholder-admin-text3
                        transition-all duration-200 outline-none
                        [&:autofill]:transition-colors [&:autofill]:duration-[9999999s]
                        ${inputBorderClass}
                        ${className}
                    `}
                    {...props}
                />
                {(displayError || success) && (
                    <div className={messageClass}>
                        {messageIcon}
                        {displayError || success}
                    </div>
                )}
            </div>
        );
    }
);

AdminInput.displayName = 'AdminInput';
export default AdminInput;
