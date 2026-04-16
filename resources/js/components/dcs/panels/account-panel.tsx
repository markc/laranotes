import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, Mail, Settings, ShieldCheck, Sliders, User } from 'lucide-react';
import { useCanHints } from '@/hooks/use-can-hints';

export function AccountPanel() {
    const user = usePage().props.auth?.user;
    const role = usePage().props.auth?.role;
    const canHints = useCanHints();

    const logout = () => {
        router.post('/logout');
    };

    if (!user) {
        return (
            <div className="flex h-full flex-col px-4 py-4">
                <Link
                    href="/login"
                    className="rounded-md border px-3 py-2 text-center text-sm hover:bg-[var(--scheme-accent-subtle)]"
                    style={{ borderColor: 'var(--glass-border)' }}
                >
                    Log in
                </Link>
            </div>
        );
    }

    return (
        <div className="flex h-full flex-col px-4 py-4">
            <div
                className="flex items-center gap-3 rounded-md border p-3"
                style={{ borderColor: 'var(--glass-border)' }}
            >
                <div
                    className="flex h-10 w-10 items-center justify-center rounded-full"
                    style={{
                        background: 'var(--scheme-accent)',
                        color: 'var(--scheme-accent-fg)',
                    }}
                >
                    <User className="h-5 w-5" />
                </div>
                <div className="min-w-0 flex-1">
                    <div className="truncate text-sm font-semibold">
                        {user.name}
                    </div>
                    <div
                        className="truncate text-xs"
                        style={{ color: 'var(--scheme-fg-muted)' }}
                    >
                        {user.email}
                    </div>
                    {role && (
                        <div
                            className="mt-1 inline-block rounded px-1.5 py-0.5 text-[10px] font-medium tracking-wider uppercase"
                            style={{
                                background: 'var(--scheme-accent-subtle)',
                                color: 'var(--scheme-fg-muted)',
                            }}
                        >
                            {role}
                        </div>
                    )}
                </div>
            </div>

            {canHints.manageUsers && (
                <>
                    <Link
                        href="/admin/users"
                        className="mt-3 flex items-center gap-2 rounded-md px-2 py-2 text-sm hover:bg-[var(--scheme-accent-subtle)]"
                    >
                        <ShieldCheck
                            className="h-4 w-4"
                            style={{ color: 'var(--scheme-fg-muted)' }}
                        />
                        Manage users
                    </Link>
                    <Link
                        href="/admin/settings"
                        className="mt-1 flex items-center gap-2 rounded-md px-2 py-2 text-sm hover:bg-[var(--scheme-accent-subtle)]"
                    >
                        <Sliders
                            className="h-4 w-4"
                            style={{ color: 'var(--scheme-fg-muted)' }}
                        />
                        Site settings
                    </Link>
                </>
            )}

            {canHints.moderate && (
                <Link
                    href="/invites"
                    className="mt-1 flex items-center gap-2 rounded-md px-2 py-2 text-sm hover:bg-[var(--scheme-accent-subtle)]"
                >
                    <Mail
                        className="h-4 w-4"
                        style={{ color: 'var(--scheme-fg-muted)' }}
                    />
                    Invites
                </Link>
            )}

            <Link
                href="/settings/profile"
                className="mt-3 flex items-center gap-2 rounded-md px-2 py-2 text-sm hover:bg-[var(--scheme-accent-subtle)]"
            >
                <Settings
                    className="h-4 w-4"
                    style={{ color: 'var(--scheme-fg-muted)' }}
                />
                Settings
            </Link>

            <button
                onClick={logout}
                className="mt-1 flex items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-[var(--scheme-accent-subtle)]"
            >
                <LogOut
                    className="h-4 w-4"
                    style={{ color: 'var(--scheme-fg-muted)' }}
                />
                Log out
            </button>
        </div>
    );
}
