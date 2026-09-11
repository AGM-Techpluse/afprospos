import React, { SelectHTMLAttributes, forwardRef } from 'react';
import FormField from '../../Forms/FormField';
import FormErrorMessage from '../../Forms/FormErrorMessage';
import FormSuccessMessage from '../../Forms/FormSuccessMessage';
import Icon from '../../Icons/Icon';

interface AdminSelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    label: string;
    error?: string;
    success?: string;
}

const AdminSelect = forwardRef<HTMLSelectElement, AdminSelectProps>(
    ({ label, error, success, className = '', children, ...props }, ref) => {
        let borderClass = 'border-admin-border-strong focus:border-admin-blue focus:ring-admin-focus focus:ring-2';
        if (error) {
            borderClass = 'border-admin-red focus:border-admin-red focus:ring-admin-red-soft focus:ring-2';
        } else if (success) {
            borderClass = 'border-admin-green focus:border-admin-green focus:ring-admin-green-soft focus:ring-2';
        }

        return (
            <FormField label={label} labelClassName="text-xs font-semibold text-admin-text mb-[5px]">
                {(id) => (
                    <>
                        <div className="relative">
                            <select
                                id={id}
                                ref={ref}
                                className={`
                                    w-full bg-white border rounded-admin-button pl-admin-field-x pr-8 py-admin-field-y appearance-none
                                    font-admin-base text-admin-field text-admin-text
                                    transition-all duration-200 outline-none
                                    ${borderClass}
                                    ${className}
                                `}
                                {...props}
                            >
                                {children}
                            </select>
                            <Icon
                                name="chevron-down"
                                className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-admin-text3"
                            />
                        </div>
                        {error && <FormErrorMessage className="mt-1 text-xs">{error}</FormErrorMessage>}
                        {!error && success && <FormSuccessMessage className="mt-1 text-xs">{success}</FormSuccessMessage>}
                    </>
                )}
            </FormField>
        );
    }
);

AdminSelect.displayName = 'AdminSelect';
export default AdminSelect;
