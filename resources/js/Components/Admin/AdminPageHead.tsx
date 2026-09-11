import { ReactNode } from 'react';

interface AdminPageHeadProps {
    title: string;
    description?: string;
    actions?: ReactNode;
}

/** Standard admin page header — title/description on the left, primary/secondary actions on the right (UI/UX §1.2 Principle 5 — one dominant action). */
export default function AdminPageHead({ title, description, actions }: AdminPageHeadProps) {
    return (
        <div className="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 className="text-admin-xl font-bold text-admin-text">{title}</h1>
                {description && <p className="text-admin-text2 text-sm mt-1">{description}</p>}
            </div>
            {actions && <div className="flex items-center gap-2 shrink-0">{actions}</div>}
        </div>
    );
}
