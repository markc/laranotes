import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, Settings, User } from 'lucide-react';

export function AccountPanel() {
    const user = usePage().props.auth?.user;

    const logout = () => {
        router.post('/logout');
    };

    return (
        <div className="flex h-full flex-col px-4 py-4">
            {user ? (
                <>
                    <div
                        className="flex items-center gap-3 rounded-md border p-3"
                        style={{ borderColor: 'var(--glass-border)' }}
                    >
                        <div
                            className="flex h-10 w-10 items-center justify-center rounded-full"
                            style={{ background: 'var(--scheme-accent)', color: 'var(--scheme-accent-fg)' }}
                        >
                            <User className="h-5 w-5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="truncate text-sm font-semibold">{user.name}</div>
                            <div
                                className="truncate text-xs"
                                style={{ color: 'var(--scheme-fg-muted)' }}
                            >
                                {user.email}
                            </div>
                        </div>
                    </div>

                    <Link
                        href="/settings/profile"
                        className="mt-3 flex items-center gap-2 rounded-md px-2 py-2 text-sm hover:bg-[var(--scheme-accent-subtle)]"
                    >
                        <Settings className="h-4 w-4" style={{ color: 'var(--scheme-fg-muted)' }} />
                        Settings
                    </Link>

                    <button
                        onClick={logout}
                        className="mt-1 flex items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-[var(--scheme-accent-subtle)]"
                    >
                        <LogOut className="h-4 w-4" style={{ color: 'var(--scheme-fg-muted)' }} />
                        Log out
                    </button>
                </>
            ) : (
                <Link
                    href="/login"
                    className="rounded-md border px-3 py-2 text-center text-sm hover:bg-[var(--scheme-accent-subtle)]"
                    style={{ borderColor: 'var(--glass-border)' }}
                >
                    Log in
                </Link>
            )}
        </div>
    );
}
