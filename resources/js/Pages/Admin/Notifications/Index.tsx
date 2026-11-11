import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import { bodyFor, linkFor, NotificationRow, titleFor } from '../../../lib/notificationCopy';

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface NotificationsIndexProps {
    notifications: Paginated<NotificationRow>;
}

export default function NotificationsIndex({ notifications }: NotificationsIndexProps) {
    const hasUnread = notifications.data.some((notification) => notification.read_at === null);

    function goToPage(page: number) {
        router.get('/admin/notifications', { page }, { only: ['notifications'], preserveState: true, preserveScroll: true, replace: true, showProgress: false });
    }

    function markAllRead() {
        router.post('/admin/notifications/read-all', {}, { preserveScroll: true, only: ['notifications', 'staffNotifications'] });
    }

    function openNotification(notification: NotificationRow) {
        const url = linkFor(notification);

        if (notification.read_at === null) {
            router.post(
                `/admin/notifications/${notification.id}/read`,
                {},
                { preserveScroll: true, preserveState: true, onFinish: () => router.visit(url) },
            );
        } else {
            router.visit(url);
        }
    }

    return (
        <AdminShell>
            <Head title="Notifications" />

            <AdminPageHead
                title="Notifications"
                description="Approvals and alerts addressed to you."
                actions={
                    hasUnread ? (
                        <AdminButton variant="neutral" onClick={markAllRead}>
                            Mark all as read
                        </AdminButton>
                    ) : undefined
                }
            />

            {notifications.data.length === 0 ? (
                <AdminEmptyState
                    icon="bell-slash"
                    title="No notifications yet"
                    description="Approvals and alerts addressed to you will show up here."
                />
            ) : (
                <div className="bg-admin-surface rounded-admin-card border border-admin-border overflow-hidden">
                    <div className="divide-y divide-admin-border">
                        {notifications.data.map((notification) => (
                            <button
                                key={notification.id}
                                type="button"
                                onClick={() => openNotification(notification)}
                                className={`flex items-start gap-3 w-full text-left px-4 py-3 cursor-pointer hover:bg-admin-hover ${
                                    notification.read_at === null ? 'bg-admin-blue/5' : ''
                                }`}
                            >
                                <span
                                    className={`mt-1.5 w-2 h-2 rounded-full shrink-0 ${notification.read_at === null ? 'bg-admin-blue' : 'bg-transparent'}`}
                                />
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-semibold text-admin-text">{titleFor(notification)}</p>
                                    <p className="text-sm text-admin-text2">{bodyFor(notification)}</p>
                                    <p className="text-xs text-admin-text3 mt-1">{new Date(notification.created_at).toLocaleString()}</p>
                                </div>
                            </button>
                        ))}
                    </div>

                    {notifications.total > 0 && (
                        <AdminPagination
                            currentPage={notifications.current_page}
                            totalItems={notifications.total}
                            pageSize={notifications.per_page}
                            onPageChange={goToPage}
                        />
                    )}
                </div>
            )}
        </AdminShell>
    );
}
