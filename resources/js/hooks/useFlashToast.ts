import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { useToast } from './useToast';
import type { ToastKind } from './useToast';

interface FlashPayload {
    kind: ToastKind;
    title: string;
    message?: string | null;
}

/**
 * Bridges Laravel's session-flash `flash` prop (set via
 * App\Http\Controllers\Concerns\FlashesToast, shared by
 * HandleInertiaRequests) into the toast queue. Mount once per shell
 * (AdminShell/CustomerShell, next to their ToastViewport) — Laravel's
 * flash bag only survives one request, so a fresh `flash` value only
 * ever appears on the response immediately following the action that set it.
 */
export function useFlashToast(): void {
    const { flash } = usePage().props as { flash?: FlashPayload | null };
    const { toast } = useToast();

    useEffect(() => {
        if (!flash) {
            return;
        }

        toast({ kind: flash.kind, title: flash.title, message: flash.message ?? undefined });
        // `toast` is a stable context function; re-running this effect only
        // when `flash` changes (a new object each time the server sets it)
        // is what makes each flashed message fire exactly once.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash]);
}
