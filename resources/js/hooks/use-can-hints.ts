import { usePage } from '@inertiajs/react';
import type { CanHints, Role, User } from '@/types/auth';

const EMPTY_HINTS: CanHints = {
    createNotes: false,
    createFolders: false,
    moderate: false,
    manageUsers: false,
};

/**
 * Advisory UI hints for the current session. These are precomputed server-side
 * in HandleInertiaRequests and exist solely so components can hide buttons
 * without re-deriving role logic. Server policies remain authoritative — never
 * rely on these to gate state mutations.
 */
export function useCanHints(): CanHints {
    return usePage().props.auth?.canHints ?? EMPTY_HINTS;
}

export function useCurrentRole(): Role | null {
    return usePage().props.auth?.role ?? null;
}

export function useCurrentUser(): User | null {
    return usePage().props.auth?.user ?? null;
}
