import React, { TextareaHTMLAttributes, forwardRef, useState } from 'react';
import FormField from '../../Forms/FormField';
import FormErrorMessage from '../../Forms/FormErrorMessage';
import FormSuccessMessage from '../../Forms/FormSuccessMessage';

interface AdminTextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label: string;
    error?: string;
    success?: string;
}

const AdminTextarea = forwardRef<HTMLTextAreaElement, AdminTextareaProps>(
    ({ label, error, success, className = '', onBlur, onChange, ...props }, ref) => {
        const [internalError, setInternalError] = useState<string>('');

        const handleBlur = (e: React.FocusEvent<HTMLTextAreaElement>) => {
            setInternalError(!e.target.validity.valid && e.target.validity.valueMissing ? 'This field is required.' : '');
            if (onBlur) onBlur(e);
        };

        const handleChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
            if (internalError) setInternalError('');
            if (onChange) onChange(e);
        };

        const displayError = error || internalError;

        let borderClass = 'border-admin-border-strong focus:border-admin-blue focus:ring-admin-focus focus:ring-2';
        if (displayError) {
            borderClass = 'border-admin-red focus:border-admin-red focus:ring-admin-red-soft focus:ring-2';
        } else if (success) {
            borderClass = 'border-admin-green focus:border-admin-green focus:ring-admin-green-soft focus:ring-2';
        }

        return (
            <FormField label={label} labelClassName="text-xs font-semibold text-admin-text mb-[5px]">
                {(id) => (
                    <>
                        <textarea
                            id={id}
                            ref={ref}
                            rows={4}
                            onBlur={handleBlur}
                            onChange={handleChange}
                            className={`
                                w-full bg-white border rounded-admin-button px-admin-field-x py-admin-field-y resize-y
                                font-admin-base text-admin-field text-admin-text placeholder-admin-text3
                                transition-all duration-200 outline-none
                                ${borderClass}
                                ${className}
                            `}
                            {...props}
                        />
                        {displayError && <FormErrorMessage className="mt-1 text-xs">{displayError}</FormErrorMessage>}
                        {!displayError && success && <FormSuccessMessage className="mt-1 text-xs">{success}</FormSuccessMessage>}
                    </>
                )}
            </FormField>
        );
    }
);

AdminTextarea.displayName = 'AdminTextarea';
export default AdminTextarea;
