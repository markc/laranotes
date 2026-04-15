import { Link, usePage } from '@inertiajs/react';

export default function TopNav() {
    const { name } = usePage().props as { name?: string };
    return (
        <header
            className="flex h-[var(--topnav-height)] items-center justify-center border-b"
            style={{
                background: 'var(--glass)',
                backdropFilter: 'blur(20px)',
                WebkitBackdropFilter: 'blur(20px)',
                borderColor: 'var(--glass-border)',
            }}
        >
            <Link href="/dashboard">
                <h1
                    className="text-xl font-bold tracking-tight"
                    style={{ color: 'var(--scheme-accent)' }}
                >
                    {name || 'Laranotes'}
                </h1>
            </Link>
        </header>
    );
}
