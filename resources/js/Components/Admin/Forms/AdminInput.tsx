import React, { InputHTMLAttributes, forwardRef, useState } from 'react';
import FormField from '../../Forms/FormField';
import FormErrorMessage from '../../Forms/FormErrorMessage';
import FormSuccessMessage from '../../Forms/FormSuccessMessage';

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

        let inputBorderClass = 'border-admin-border-strong focus:border-admin-blue focus:ring-admin-focus focus:ring-2';
        if (displayError) {
            inputBorderClass = 'border-admin-red focus:border-admin-red focus:ring-admin-red-soft focus:ring-2';
        } else if (success) {
            inputBorderClass = 'border-admin-green focus:border-admin-green focus:ring-admin-green-soft focus:ring-2';
        }

        return (
            <FormField label={label} labelClassName="text-xs font-semibold text-admin-text mb-[5px]">
                {(id) => (
                    <>
                        <input
                            id={id}
                            ref={ref}
                            onBlur={handleBlur}
                            onChange={handleChange}
                            className={`
                                bg-white border rounded-admin-button px-admin-field-x py-admin-field-y
                                font-admin-base text-admin-field text-admin-text placeholder-admin-text3
                                transition-all duration-200 outline-none
                                [&:autofill]:transition-colors [&:autofill]:duration-[9999999s]
                                ${inputBorderClass}
                                ${className}
                            `}
                            {...props}
                        />
                        {displayError && (
                            <FormErrorMessage className="mt-1 text-xs">{displayError}</FormErrorMessage>
                        )}
                        {!displayError && success && (
                            <FormSuccessMessage className="mt-1 text-xs">{success}</FormSuccessMessage>
                        )}
                    </>
                )}
            </FormField>
        );
    }
);

AdminInput.displayName = 'AdminInput';
export default AdminInput;
