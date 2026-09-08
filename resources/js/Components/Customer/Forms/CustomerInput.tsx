import React, { InputHTMLAttributes, forwardRef } from 'react';
import Icon from '../../Shared/Icon';

interface CustomerInputProps extends InputHTMLAttributes<HTMLInputElement> {
    label: string;
    error?: string;
    success?: string;
}

const CustomerInput = forwardRef<HTMLInputElement, CustomerInputProps>(
    ({ label, error, success, className = '', ...props }, ref) => {
        let containerClass = 'flex flex-col mb-6 relative';
        let inputBorderClass = 'bg-customer-blue-soft border-transparent';
        let messageClass = '';
        let messageIcon = null;

        if (error) {
            containerClass += ' text-customer-red';
            inputBorderClass = 'bg-customer-red-soft border-transparent focus:ring-0';
            messageClass = 'text-customer-red mt-1 text-[13px] flex items-center';
            messageIcon = <Icon name="exclamation-circle" className="mr-1" />;
        } else if (success) {
            containerClass += ' text-customer-green';
            inputBorderClass = 'bg-customer-green-soft border-transparent focus:ring-0';
            messageClass = 'text-customer-green mt-1 text-[13px] flex items-center';
            messageIcon = <Icon name="check-circle" className="mr-1" />;
        } else {
            inputBorderClass = 'bg-customer-blue-soft border-transparent focus:ring-0 focus:bg-[#D5E3FC]';
        }

        return (
            <div className={containerClass}>
                <label className="text-[13px] font-customer-ui text-customer-text2 mb-1">
                    {label}
                </label>
                <input
                    ref={ref}
                    className={`
                        px-3 py-2
                        font-customer-ui text-[14px] text-customer-text placeholder-customer-muted-icon
                        transition-colors duration-200 outline-none shadow-none
                        ${inputBorderClass}
                        ${className}
                    `}
                    {...props}
                />
                {(error || success) && (
                    <div className={messageClass}>
                        {messageIcon}
                        {error || success}
                    </div>
                )}
            </div>
        );
    }
);

CustomerInput.displayName = 'CustomerInput';
export default CustomerInput;
