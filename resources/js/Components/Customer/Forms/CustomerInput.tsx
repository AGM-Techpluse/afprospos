import React, { InputHTMLAttributes, forwardRef, useState } from 'react';
import FormField from '../../Forms/FormField';
import FormErrorMessage from '../../Forms/FormErrorMessage';
import FormSuccessMessage from '../../Forms/FormSuccessMessage';

interface CustomerInputProps extends InputHTMLAttributes<HTMLInputElement> {
    label: string;
    error?: string;
    success?: string;
}

const CustomerInput = forwardRef<HTMLInputElement, CustomerInputProps>(
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
                setInternalError(''); // Clear error on typing
            }
            if (onChange) onChange(e);
        };

        const displayError = error || internalError;

        let inputBorderClass = 'border-customer-input-border focus:border-customer-blue';
        if (displayError) {
            inputBorderClass = 'border-customer-red focus:border-customer-red';
        } else if (success) {
            inputBorderClass = 'border-customer-green focus:border-customer-green';
        }

        return (
            <FormField
                label={label}
                labelClassName="text-customer-caption font-customer-ui font-semibold text-customer-text2 mb-1"
                containerClassName="flex flex-col mb-6 relative"
            >
                {(id) => (
                    <>
                        <input
                            id={id}
                            ref={ref}
                            onBlur={handleBlur}
                            onChange={handleChange}
                            className={`
                                w-full bg-transparent border-0 border-b-2 px-0 py-2
                                font-customer-ui text-customer-field text-customer-text placeholder-customer-muted-icon
                                transition-colors duration-200 outline-none shadow-none focus:ring-0
                                [&:autofill]:transition-colors [&:autofill]:duration-[9999999s]
                                ${inputBorderClass}
                                ${className}
                            `}
                            {...props}
                        />
                        {displayError && (
                            <FormErrorMessage className="mt-1 text-customer-caption">{displayError}</FormErrorMessage>
                        )}
                        {!displayError && success && (
                            <FormSuccessMessage className="mt-1 text-customer-caption">{success}</FormSuccessMessage>
                        )}
                    </>
                )}
            </FormField>
        );
    }
);

CustomerInput.displayName = 'CustomerInput';
export default CustomerInput;
