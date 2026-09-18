import { useToast } from '../../hooks/useToast';
import ScallopedIconButton from './ScallopedIconButton';

/**
 * Primary floating action, same on both breakpoints now that the mobile
 * bottom taskbar has been removed in favor of the hamburger drawer for
 * navigation. No "request a repair" flow exists yet, so it's a real,
 * honest placeholder — a toast, not a dead click — rather than linking
 * to a page that doesn't exist.
 */
export default function CustomerFab() {
    const { toast } = useToast();

    function requestRepair() {
        toast({ kind: 'info', title: 'Coming soon', message: 'Requesting a repair will be available here soon.' });
    }

    return (
        <div className="print:hidden fixed bottom-6 right-6 sm:right-10 z-40">
            <ScallopedIconButton icon="plus-lg" onClick={requestRepair} ariaLabel="Request a repair" size={56} />
        </div>
    );
}
