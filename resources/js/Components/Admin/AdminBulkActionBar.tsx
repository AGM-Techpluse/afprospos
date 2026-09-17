import { ReactNode } from 'react';
import AdminButton from './AdminButton';

interface AdminBulkActionBarProps {
    selectedCount: number;
    onClear: () => void;
    actions: ReactNode;
}

/** Shown above a selectable ResponsiveDataTable once at least one row is checked — "N selected", the page's own bulk actions, and a way to clear the selection. Reused across every bulk-action page instead of each one rebuilding this bar. */
export default function AdminBulkActionBar({ selectedCount, onClear, actions }: AdminBulkActionBarProps) {
    if (selectedCount === 0) {
        return null;
    }

    return (
        <div className="flex items-center gap-3 flex-wrap bg-admin-blue-soft border border-admin-blue rounded-admin-card px-3.5 py-2.5 mb-3">
            <span className="text-xs font-semibold text-admin-blue">{selectedCount} selected</span>
            <div className="flex items-center gap-2 flex-wrap">{actions}</div>
            <div className="flex-1" />
            <AdminButton type="button" variant="neutral" onClick={onClear}>
                Clear selection
            </AdminButton>
        </div>
    );
}
