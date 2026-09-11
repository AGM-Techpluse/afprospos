import { ReactNode, useState } from 'react';
import Icon from '../Icons/Icon';
import Skeleton from '../Feedback/Skeleton';

export interface DataTableColumn<T> {
    key: string;
    label: string;
    render?: (row: T) => ReactNode;
}

interface ResponsiveDataTableProps<T> {
    columns: DataTableColumn<T>[];
    rows: T[];
    getRowKey: (row: T) => string | number;
    /** Column key shown as the collapsed mobile card's title. */
    mobilePrimaryField: string;
    /** Column key shown next to the title on the collapsed mobile card (typically a status badge column). */
    mobileStatusField?: string;
    /** Column keys shown, one per row with a dashed separator, once a mobile card is expanded. */
    mobileDetailFields: string[];
    /** Per-row action slot (e.g. a three-dot menu on desktop, Edit/Delete on mobile) — business actions vary per resource, so this isn't hardcoded (UI/UX §9.3). */
    renderActions?: (row: T) => ReactNode;
    emptyState?: ReactNode;
    /** Search/filter controls, rendered as the table's own header bar (prototype `.a-table-toolbar`) — not a separate block above the table. */
    toolbar?: ReactNode;
    /** Pagination or a "Showing X–Y of Z" summary, rendered as the table's own footer bar (prototype `.a-table-foot`). */
    footer?: ReactNode;
    /** True while a partial (data-only) refresh is in flight — renders skeleton rows in place of real ones without touching the toolbar/headers. */
    loading?: boolean;
}

/**
 * UI/UX §9.1 (desktop table) + §9.5/§9.6 (mobile accordion row-cards) as one
 * component, not three — the two are the same data at two breakpoints, not
 * two designs. Single-open accordion below 640px, per §9.6. Toolbar/footer
 * are optional slots so a table can still be used bare (e.g. Roles).
 */
export default function ResponsiveDataTable<T>({
    columns,
    rows,
    getRowKey,
    mobilePrimaryField,
    mobileStatusField,
    mobileDetailFields,
    renderActions,
    emptyState,
    toolbar,
    footer,
    loading = false,
}: ResponsiveDataTableProps<T>) {
    const [openRowKey, setOpenRowKey] = useState<string | number | null>(null);

    const columnMap = new Map(columns.map((column) => [column.key, column]));
    const renderCell = (key: string, row: T): ReactNode => {
        const column = columnMap.get(key);
        if (!column) return null;
        return column.render ? column.render(row) : String((row as Record<string, unknown>)[key] ?? '');
    };

    const isEmpty = !loading && rows.length === 0;
    const skeletonRowCount = Math.min(Math.max(rows.length, 3), 8);

    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card overflow-hidden">
            {toolbar && (
                <div className="flex items-center gap-2 flex-wrap px-3.5 py-2.5 border-b border-admin-border">
                    {toolbar}
                </div>
            )}

            {isEmpty && emptyState}

            {!isEmpty && (
                <>
                    {/* Desktop table (>= 640px) */}
                    <div className="hidden sm:block overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="bg-admin-table-head">
                                    {columns.map((column) => (
                                        <th
                                            key={column.key}
                                            className="text-left text-admin-xs font-semibold text-admin-text2 px-admin-table-cell-x py-admin-table-cell-y"
                                        >
                                            {column.label}
                                        </th>
                                    ))}
                                    {renderActions && <th className="w-10" aria-hidden="true" />}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-admin-border">
                                {loading
                                    ? Array.from({ length: skeletonRowCount }).map((_, i) => (
                                          <tr key={`skeleton-${i}`}>
                                              {columns.map((column) => (
                                                  <td key={column.key} className="px-admin-table-cell-x py-admin-table-cell-y">
                                                      <Skeleton className="h-4 w-full max-w-[140px]" />
                                                  </td>
                                              ))}
                                              {renderActions && (
                                                  <td className="px-admin-table-cell-x py-admin-table-cell-y">
                                                      <Skeleton className="h-4 w-16" />
                                                  </td>
                                              )}
                                          </tr>
                                      ))
                                    : rows.map((row) => (
                                          <tr key={getRowKey(row)} className="hover:bg-admin-hover">
                                              {columns.map((column) => (
                                                  <td
                                                      key={column.key}
                                                      className="text-admin-table-cell text-admin-text px-admin-table-cell-x py-admin-table-cell-y"
                                                  >
                                                      {renderCell(column.key, row)}
                                                  </td>
                                              ))}
                                              {renderActions && (
                                                  <td className="px-admin-table-cell-x py-admin-table-cell-y text-right">
                                                      {renderActions(row)}
                                                  </td>
                                              )}
                                          </tr>
                                      ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile accordion row-cards (< 640px) */}
                    <div className="sm:hidden flex flex-col">
                        {loading
                            ? Array.from({ length: skeletonRowCount }).map((_, i) => (
                                  <div key={`skeleton-${i}`} className="px-admin-table-cell-x py-admin-table-cell-y border-b border-admin-border last:border-b-0">
                                      <Skeleton className="h-4 w-2/3 mb-2" />
                                      <Skeleton className="h-3 w-1/3" />
                                  </div>
                              ))
                            : rows.map((row) => {
                                  const key = getRowKey(row);
                                  const isOpen = openRowKey === key;

                                  return (
                                      <div key={key} className="border-b border-admin-border last:border-b-0">
                                          <button
                                              type="button"
                                              onClick={() => setOpenRowKey(isOpen ? null : key)}
                                              className="w-full flex items-center justify-between gap-2 px-admin-table-cell-x py-admin-table-cell-y text-left"
                                              aria-expanded={isOpen}
                                          >
                                              <span className="flex items-center gap-2 min-w-0">
                                                  <span className="font-semibold text-admin-text text-admin-table-cell truncate">
                                                      {renderCell(mobilePrimaryField, row)}
                                                  </span>
                                                  {mobileStatusField && renderCell(mobileStatusField, row)}
                                              </span>
                                              <Icon
                                                  name="chevron-down"
                                                  className={`text-admin-text3 shrink-0 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`}
                                              />
                                          </button>

                                          {isOpen && (
                                              <div className="px-admin-table-cell-x pb-admin-table-cell-y">
                                                  {mobileDetailFields.map((fieldKey) => {
                                                      const column = columnMap.get(fieldKey);
                                                      if (!column) return null;
                                                      return (
                                                          <div
                                                              key={fieldKey}
                                                              className="flex items-center justify-between py-2 border-t border-dashed border-admin-border text-admin-table-cell"
                                                          >
                                                              <span className="text-admin-text2">{column.label}</span>
                                                              <span className="text-admin-text">{renderCell(fieldKey, row)}</span>
                                                          </div>
                                                      );
                                                  })}
                                                  {renderActions && (
                                                      <div className="flex items-center gap-2 pt-2 border-t border-dashed border-admin-border">
                                                          {renderActions(row)}
                                                      </div>
                                                  )}
                                              </div>
                                          )}
                                      </div>
                                  );
                              })}
                    </div>
                </>
            )}

            {footer && !isEmpty && <div className="border-t border-admin-border">{footer}</div>}
        </div>
    );
}
