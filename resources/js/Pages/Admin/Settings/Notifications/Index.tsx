import { Head } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import SettingsSubNav from '../../../../Components/Admin/SettingsSubNav';
import AdminBadge from '../../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';

interface FailedNotification {
    id: number;
    event_type: string;
    source_module: string;
    source_id: number;
    category: string;
    status: string;
    created_at: string;
}

interface NotificationSettingsProps {
    resendConfigured: boolean;
    defaultMailer: string;
    mandatoryCategories: string[];
    maxDeliveryAttempts: number;
    recentFailed: FailedNotification[];
}

export default function NotificationSettingsIndex({
    resendConfigured,
    defaultMailer,
    mandatoryCategories,
    maxDeliveryAttempts,
    recentFailed,
}: NotificationSettingsProps) {
    return (
        <AdminShell>
            <Head title="Notification settings" />

            <AdminPageHead
                title="Notification settings"
                description="Provider status and delivery diagnostics. Credentials themselves live in the server's .env file, not here — this page never displays or accepts a secret."
            />

            <SettingsSubNav />

            <div className="max-w-3xl space-y-6">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4">
                    <h2 className="text-sm font-semibold text-admin-text mb-3">Email providers</h2>
                    <div className="flex items-center justify-between py-2 border-b border-admin-border">
                        <span className="text-sm text-admin-text">Resend</span>
                        <AdminBadge status={resendConfigured ? 'success' : 'neutral'}>
                            {resendConfigured ? 'Configured' : 'Not configured'}
                        </AdminBadge>
                    </div>
                    <div className="flex items-center justify-between py-2">
                        <span className="text-sm text-admin-text">Fallback mailer ({defaultMailer})</span>
                        <AdminBadge status="success">Always available</AdminBadge>
                    </div>
                    <p className="text-xs text-admin-text2 mt-3">
                        To set or change the Resend API key, update <code className="bg-admin-hover px-1 rounded">RESEND_API_KEY</code> in
                        the server's <code className="bg-admin-hover px-1 rounded">.env</code> file and restart the queue worker.
                    </p>
                </div>

                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4">
                    <h2 className="text-sm font-semibold text-admin-text mb-3">Delivery policy</h2>
                    <div className="flex items-center justify-between py-2 border-b border-admin-border">
                        <span className="text-sm text-admin-text">Max delivery attempts</span>
                        <span className="text-sm font-semibold text-admin-text">{maxDeliveryAttempts}</span>
                    </div>
                    <div className="flex items-center justify-between py-2">
                        <span className="text-sm text-admin-text">Mandatory categories</span>
                        <span className="text-sm font-semibold text-admin-text">{mandatoryCategories.join(', ')}</span>
                    </div>
                    <p className="text-xs text-admin-text2 mt-3">
                        Mandatory categories ignore a customer's channel preference (NOTIF-BR-16). Change these in{' '}
                        <code className="bg-admin-hover px-1 rounded">config/afprospos.php</code>.
                    </p>
                </div>

                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4">
                    <h2 className="text-sm font-semibold text-admin-text mb-3">Recent failed notifications</h2>
                    {recentFailed.length === 0 ? (
                        <AdminEmptyState icon="bell-slash" title="No failed notifications" description="Everything sent recently was delivered." />
                    ) : (
                        <div className="divide-y divide-admin-border">
                            {recentFailed.map((notification) => (
                                <div key={notification.id} className="flex items-center justify-between py-2">
                                    <div>
                                        <span className="text-sm text-admin-text font-medium">{notification.event_type}</span>
                                        <span className="text-xs text-admin-text2 ml-2">
                                            {notification.source_module} #{notification.source_id}
                                        </span>
                                    </div>
                                    <span className="text-xs text-admin-text2">{new Date(notification.created_at).toLocaleString()}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AdminShell>
    );
}
