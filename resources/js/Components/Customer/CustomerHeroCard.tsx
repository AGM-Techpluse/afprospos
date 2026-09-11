import Icon from '../Icons/Icon';

interface ActiveRepair {
    device: string;
    statusLabel: string;
    description: string;
    progressPercent: number;
}

interface CustomerHeroCardProps {
    activeRepair?: ActiveRepair;
}

/**
 * UI/UX §14C.10 — summarizes at most the customer's single most-relevant
 * open item. No backend Repair data exists yet, so the empty-state branch
 * (§24) is what actually renders today; the active-repair branch is real
 * and ready for when Repairs data starts flowing — no rebuild needed then,
 * just a prop.
 */
export default function CustomerHeroCard({ activeRepair }: CustomerHeroCardProps) {
    if (!activeRepair) {
        return (
            <div className="bg-customer-hero-gradient rounded-customer-hero text-white p-6 mb-6 shadow-customer-strong flex flex-col items-center text-center">
                <div className="w-12 h-12 rounded-customer-pill bg-white/20 flex items-center justify-center text-white text-xl mb-3">
                    <Icon name="tools" />
                </div>
                <h3 className="font-bold text-customer-lg mb-1">No active repairs</h3>
                <p className="text-customer-caption opacity-85 mb-4">
                    When you book a repair, its live status will show up here.
                </p>
                <span
                    aria-disabled="true"
                    className="inline-flex items-center gap-2 rounded-customer-pill bg-white text-customer-blue text-customer-button font-bold px-customer-btn-x py-customer-btn-y cursor-not-allowed opacity-90"
                >
                    Request a repair
                    <span className="text-xs uppercase font-semibold opacity-70">Soon</span>
                </span>
            </div>
        );
    }

    return (
        <div className="bg-customer-hero-gradient rounded-customer-hero text-white p-6 mb-6 shadow-customer-strong">
            <span className="inline-flex items-center gap-1.5 bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-customer-pill mb-3">
                <span className="w-1.5 h-1.5 rounded-full bg-customer-yellow" aria-hidden="true" />
                {activeRepair.statusLabel}
            </span>
            <h3 className="font-bold text-customer-lg mb-1">{activeRepair.device}</h3>
            <p className="text-customer-caption opacity-85 mb-4">{activeRepair.description}</p>
            <div className="h-1.5 rounded-full bg-white/25 mb-4 overflow-hidden">
                <div
                    className="h-full rounded-full bg-white"
                    style={{ width: `${Math.min(100, Math.max(0, activeRepair.progressPercent))}%` }}
                />
            </div>
        </div>
    );
}
