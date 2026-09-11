import Icon from '../Icons/Icon';

interface AdminPaginationProps {
    currentPage: number;
    totalItems: number;
    pageSize: number;
    onPageChange: (page: number) => void;
}

/** UI/UX §9.4 — "Showing X–Y of Z" left, compact pager right, 24px min control target. */
export default function AdminPagination({ currentPage, totalItems, pageSize, onPageChange }: AdminPaginationProps) {
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    const start = totalItems === 0 ? 0 : (currentPage - 1) * pageSize + 1;
    const end = Math.min(currentPage * pageSize, totalItems);

    return (
        <div className="flex items-center justify-between px-4 py-3 text-xs text-admin-text2">
            <span>
                Showing {start}–{end} of {totalItems}
            </span>
            <div className="flex items-center gap-1">
                <button
                    type="button"
                    onClick={() => onPageChange(currentPage - 1)}
                    disabled={currentPage <= 1}
                    aria-label="Previous page"
                    className="w-6 h-6 flex items-center justify-center rounded-admin-button hover:bg-admin-hover disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    <Icon name="chevron-left" />
                </button>
                <span className="font-medium text-admin-text px-1">
                    Page {currentPage} of {totalPages}
                </span>
                <button
                    type="button"
                    onClick={() => onPageChange(currentPage + 1)}
                    disabled={currentPage >= totalPages}
                    aria-label="Next page"
                    className="w-6 h-6 flex items-center justify-center rounded-admin-button hover:bg-admin-hover disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    <Icon name="chevron-right" />
                </button>
            </div>
        </div>
    );
}
