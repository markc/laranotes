import { router, usePage } from '@inertiajs/react';

export function ImpersonationBanner() {
    const impersonator = usePage().props.auth?.impersonator;

    if (!impersonator) {
        return null;
    }

    const stop = () => {
        router.post('/impersonate/stop');
    };

    return (
        <div className="flex items-center justify-center gap-3 bg-amber-500 px-4 py-1.5 text-xs font-medium text-amber-950">
            <span>
                Viewing as another user. Impersonated by{' '}
                <span className="font-semibold">{impersonator.name}</span>.
            </span>
            <button
                onClick={stop}
                className="rounded bg-amber-950/20 px-2 py-0.5 hover:bg-amber-950/30"
            >
                Return to admin
            </button>
        </div>
    );
}
