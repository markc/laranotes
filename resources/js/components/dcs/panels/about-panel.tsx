import { Github } from 'lucide-react';

export function AboutPanel() {
    return (
        <div className="flex flex-col gap-4 px-4 py-4 text-sm" style={{ color: 'var(--scheme-fg-primary)' }}>
            <div>
                <h3 className="mb-1 text-base font-bold" style={{ color: 'var(--scheme-accent)' }}>
                    Laranotes
                </h3>
                <p style={{ color: 'var(--scheme-fg-secondary)' }}>
                    A shared markdown note-taking app built with Laravel, Inertia, and React.
                </p>
            </div>

            <div>
                <h4 className="mb-2 text-xs font-bold uppercase tracking-wider" style={{ color: 'var(--scheme-fg-muted)' }}>
                    Stack
                </h4>
                <ul className="flex flex-col gap-1 text-xs" style={{ color: 'var(--scheme-fg-secondary)' }}>
                    <li>Laravel 13 (PHP 8.3+)</li>
                    <li>Inertia v3 + React 19 + TypeScript</li>
                    <li>Tailwind CSS 4</li>
                    <li>CodeMirror 6 markdown editor</li>
                    <li>SQLite storage</li>
                </ul>
            </div>

            <div>
                <h4 className="mb-2 text-xs font-bold uppercase tracking-wider" style={{ color: 'var(--scheme-fg-muted)' }}>
                    Keyboard
                </h4>
                <ul className="flex flex-col gap-1 text-xs" style={{ color: 'var(--scheme-fg-secondary)' }}>
                    <li><kbd className="rounded border px-1">Ctrl</kbd> + <kbd className="rounded border px-1">K</kbd> — Search</li>
                    <li><kbd className="rounded border px-1">Ctrl</kbd> + <kbd className="rounded border px-1">S</kbd> — Force save</li>
                </ul>
            </div>

            <a
                href="https://github.com/markc/laranotes"
                target="_blank"
                rel="noreferrer"
                className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--scheme-accent-subtle)]"
                style={{ borderColor: 'var(--glass-border)', color: 'var(--scheme-fg-primary)' }}
            >
                <Github className="h-3.5 w-3.5" />
                github.com/markc/laranotes
            </a>
        </div>
    );
}
