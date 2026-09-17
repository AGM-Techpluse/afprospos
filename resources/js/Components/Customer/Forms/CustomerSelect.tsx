import React, { SelectHTMLAttributes, forwardRef } from 'react';
import FormField from '../../Forms/FormField';
import FormErrorMessage from '../../Forms/FormErrorMessage';
import FormSuccessMessage from '../../Forms/FormSuccessMessage';
import Icon from '../../Icons/Icon';

interface CustomerSelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    label: string;
    error?: string;
    success?: string;
}

/** The Customer-theme select field — no formal equivalent existed yet (only CustomerInput did); mirrors CustomerInput's underline styling and AdminSelect's chevron/wrapper structure. */
const CustomerSelect = forwardRef<HTMLSelectElement, CustomerSelectProps>(
    ({ label, error, success, className = '', children, ...props }, ref) => {
        let borderClass = 'border-customer-input-border focus:border-customer-blue';
        if (error) {
            borderClass = 'border-customer-red focus:border-customer-red';
        } else if (success) {
            borderClass = 'border-customer-green focus:border-customer-green';
        }

        return (
            <FormField
                label={label}
                labelClassName="text-customer-caption font-customer-ui font-semibold text-customer-text2 mb-1"
                containerClassName="flex flex-col mb-6 relative"
            >
                {(id) => (
                    <>
                        <div className="relative">
                            <select
                                id={id}
                                ref={ref}
                                className={`
                                    w-full bg-transparent border-0 border-b-2 px-0 py-2 pr-6 appearance-none
                                    font-customer-ui text-customer-field text-customer-text
                                    transition-colors duration-200 outline-none shadow-none focus:ring-0
                                    ${borderClass}
                                    ${className}
                                `}
                                {...props}
                            >
                                {children}
                            </select>
                            <Icon name="chevron-down" className="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 text-customer-muted-icon" />
                        </div>
                        {error && <FormErrorMessage className="mt-1 text-customer-caption">{error}</FormErrorMessage>}
                        {!error && success && <FormSuccessMessage className="mt-1 text-customer-caption">{success}</FormSuccessMessage>}
                    </>
                )}
            </FormField>
        );
    }
);

CustomerSelect.displayName = 'CustomerSelect';
export default CustomerSelect;
