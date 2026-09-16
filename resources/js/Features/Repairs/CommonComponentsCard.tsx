import { useState } from 'react';
import { router } from '@inertiajs/react';
import AdminButton from '../../Components/Admin/AdminButton';

const COMMON_COMPONENTS = [
    { key: 'screen', label: 'Screen / display' },
    { key: 'battery', label: 'Battery' },
    { key: 'camera', label: 'Camera' },
    { key: 'speaker', label: 'Speaker' },
    { key: 'microphone', label: 'Microphone' },
    { key: 'charging_port', label: 'Charging port' },
    { key: 'power_button', label: 'Power button' },
    { key: 'volume_buttons', label: 'Volume buttons' },
    { key: 'sim_tray', label: 'SIM tray' },
    { key: 'vibration_motor', label: 'Vibration motor' },
    { key: 'wifi_bluetooth', label: 'Wi-Fi / Bluetooth' },
    { key: 'back_cover', label: 'Back cover / housing' },
];

interface CommonComponentsCardProps {
    submitUrl: string;
    diagnosedComponents: string[];
    disabled?: boolean;
}

function postComponent(submitUrl: string, component: string): Promise<void> {
    return new Promise((resolve, reject) => {
        router.post(
            submitUrl,
            { component, condition: 'working', notes: '', outcome: '' },
            { preserveScroll: true, preserveState: true, showProgress: false, onSuccess: () => resolve(), onError: () => reject() },
        );
    });
}

/** Bulk "mark as working" for the components most repairs touch, so a technician isn't retyping the same handful of names every job — anything not listed still goes through the manual form. */
export default function CommonComponentsCard({ submitUrl, diagnosedComponents, disabled = false }: CommonComponentsCardProps) {
    const [selected, setSelected] = useState<string[]>([]);
    const [submitting, setSubmitting] = useState(false);

    const recorded = new Set(diagnosedComponents.map((c) => c.toLowerCase()));
    const available = COMMON_COMPONENTS.filter((c) => !recorded.has(c.key));

    function toggle(key: string) {
        setSelected((current) => (current.includes(key) ? current.filter((k) => k !== key) : [...current, key]));
    }

    async function markSelectedWorking() {
        setSubmitting(true);
        try {
            for (const key of selected) {
                await postComponent(submitUrl, key);
            }
            setSelected([]);
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
            <h3 className="text-sm font-semibold text-admin-text mb-1">Common components</h3>
            <p className="text-xs text-admin-text3 mb-4">Check off what's already confirmed working, then record them in one go.</p>

            {available.length === 0 ? (
                <p className="text-xs text-admin-text3">All common components have been recorded for this job.</p>
            ) : (
                <div className="flex flex-col gap-2 mb-4">
                    {available.map((component) => (
                        <label key={component.key} className="flex items-center gap-2 text-sm text-admin-text">
                            <input
                                type="checkbox"
                                checked={selected.includes(component.key)}
                                onChange={() => toggle(component.key)}
                                disabled={disabled || submitting}
                            />
                            {component.label}
                        </label>
                    ))}
                </div>
            )}

            {recorded.size > 0 && (
                <p className="text-xs text-admin-text3 mb-4">
                    Already recorded: {[...recorded].map((key) => COMMON_COMPONENTS.find((c) => c.key === key)?.label ?? key).join(', ')}
                </p>
            )}

            <AdminButton
                type="button"
                variant="neutral"
                onClick={markSelectedWorking}
                disabled={disabled || selected.length === 0}
                isLoading={submitting}
                loadingText="Recording..."
            >
                Mark {selected.length > 0 ? selected.length : ''} selected as working
            </AdminButton>
        </div>
    );
}
