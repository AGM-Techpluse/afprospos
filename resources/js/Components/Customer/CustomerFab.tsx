import Icon from '../Icons/Icon';
import { useToast } from '../../hooks/useToast';

/**
 * UI/UX §8.7 — primary mobile action, 50px circle overlapping the taskbar;
 * on desktop it floats above the content instead (§27). No "request a
 * repair" flow exists yet, so it's a real, honest placeholder — a toast,
 * not a dead click — rather than linking to a page that doesn't exist.
 */
export default function CustomerFab() {
    const { toast } = useToast();

    return (
        <button
            type="button"
            onClick={() => toast({ kind: 'info', title: 'Coming soon', message: 'Requesting a repair will be available here soon.' })}
            aria-label="Request a repair"
            className="hidden sm:flex fixed bottom-6 right-6 sm:right-10 w-13 h-13 rounded-customer-pill bg-customer-blue text-white items-center justify-center shadow-customer-strong text-lg z-40"
        >
            <Icon name="plus-lg" />
        </button>
    );
}
