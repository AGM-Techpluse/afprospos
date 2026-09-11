import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import Icon from '../Icons/Icon';

interface AdminPageHeadProps {
    title: string;
    description?: string;
    actions?: ReactNode;
    /** Nested pages (Create/Edit/Show) pass their parent Index route here so a viewer isn't stuck relying on the browser's own back button. */
    backHref?: string;
    backLabel?: string;
}

/** Standard admin page header — title/description on the left, primary/secondary actions on the right (UI/UX §1.2 Principle 5 — one dominant action). */
export default function AdminPageHead({ title, description, actions, backHref, backLabel }: AdminPageHeadProps) {
    return (
        <div className="mb-6">
            {backHref && (
                <Link
                    href={backHref}
                    className="inline-flex items-center gap-1.5 text-xs font-semibold text-admin-text2 hover:text-admin-text mb-2"
                >
                    <Icon name="arrow-left" />
                    {backLabel ?? 'Back'}
                </Link>
            )}
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h1 className="text-admin-xl font-bold text-admin-text">{title}</h1>
                    {description && <p className="text-admin-text2 text-sm mt-1">{description}</p>}
                </div>
                {actions && <div className="flex items-center gap-2 shrink-0">{actions}</div>}
            </div>
        </div>
    );
}
