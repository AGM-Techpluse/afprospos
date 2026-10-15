import Icon from '../../Components/Icons/Icon';
import AdminBadge, { AdminBadgeStatus } from '../../Components/Admin/AdminBadge';

/** The stakeholder's stages. Parts being fitted belongs to "In Progress", so awaiting_parts is shown at the In Progress step. */
const HAPPY_PATH = ['received', 'diagnosing', 'awaiting_authorization', 'in_progress', 'qc_pending', 'handover', 'completed'] as const;

const STEP_LABEL: Record<string, string> = {
    received: 'Received',
    diagnosing: 'Functional Test & Diagnosis',
    awaiting_authorization: 'Quote & Authorization',
    awaiting_parts: 'In Progress',
    in_progress: 'In Progress',
    qc_pending: 'Quality Check',
    handover: 'Handover',
    cancelled: 'Cancelled',
    completed: 'Completed',
};

const EXCEPTION_LABEL: Record<string, string> = {
    payment_overdue: 'Payment overdue',
    expired_cancelled: 'Expired / cancelled',
    unrepairable: 'Unrepairable',
    failed_requires_resolution: 'Failed — requires resolution',
};

const EXCEPTION_BADGE: Record<string, AdminBadgeStatus> = {
    payment_overdue: 'warning',
    expired_cancelled: 'danger',
    unrepairable: 'danger',
    failed_requires_resolution: 'danger',
};

interface RepairStatusStepperProps {
    status: string;
}

/** Visual state-machine progress indicator (Implementation Plan Phase 6 UI list) — the "happy path" as a stepper, exceptional states as a standalone badge instead of forced into the linear flow. */
export default function RepairStatusStepper({ status }: RepairStatusStepperProps) {
    if (status in EXCEPTION_LABEL) {
        return (
            <div className="flex items-center gap-2">
                <AdminBadge status={EXCEPTION_BADGE[status]}>{EXCEPTION_LABEL[status]}</AdminBadge>
            </div>
        );
    }

    const currentStep = status === 'awaiting_parts' ? 'in_progress' : status;
    const currentIndex = HAPPY_PATH.indexOf(currentStep as (typeof HAPPY_PATH)[number]);

    return (
        <ol className="flex items-center gap-1 flex-wrap">
            {HAPPY_PATH.map((step, index) => {
                const isDone = index < currentIndex;
                const isCurrent = index === currentIndex;

                return (
                    <li key={step} className="flex items-center gap-1">
                        <span
                            className={`flex items-center gap-1.5 rounded-admin-badge px-2 py-1 text-xs font-semibold ${
                                isCurrent
                                    ? 'bg-admin-blue text-white'
                                    : isDone
                                      ? 'bg-admin-green-soft text-admin-green'
                                      : 'bg-admin-hover text-admin-text3'
                            }`}
                        >
                            {isDone && <Icon name="check-lg" />}
                            {STEP_LABEL[step]}
                        </span>
                        {index < HAPPY_PATH.length - 1 && <Icon name="chevron-right" className="text-admin-text3 text-xs" />}
                    </li>
                );
            })}
        </ol>
    );
}
