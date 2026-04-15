import { Link, router, usePage } from '@inertiajs/react';
import { FilePlus, FolderPlus, LayoutGrid } from 'lucide-react';
import { useState } from 'react';
import { FolderTree } from '@/components/folder-tree';
import type { FolderNode } from '@/types/models';

export function FoldersPanel() {
    const { props } = usePage<{ folderTree?: FolderNode[] }>();
    const folderTree = props.folderTree ?? [];

    const activeNoteId =
        typeof window !== 'undefined'
            ? (() => {
                  const match = window.location.pathname.match(/\/notes\/(\d+)/);
                  return match ? parseInt(match[1], 10) : null;
              })()
            : null;

    const [creating, setCreating] = useState(false);
    const [name, setName] = useState('');

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const trimmed = name.trim();
        if (!trimmed) {
            setCreating(false);
            return;
        }
        router.post(
            '/folders',
            { name: trimmed, parent_id: null },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCreating(false);
                    setName('');
                },
            },
        );
    };

    return (
        <div className="flex h-full flex-col">
            <div className="flex flex-col gap-1 px-3 py-2">
                <Link
                    href="/dashboard"
                    className="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-[var(--scheme-accent-subtle)]"
                >
                    <LayoutGrid className="h-4 w-4" style={{ color: 'var(--scheme-fg-muted)' }} />
                    Dashboard
                </Link>
                <Link
                    href="/notes/create"
                    className="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-[var(--scheme-accent-subtle)]"
                >
                    <FilePlus className="h-4 w-4" style={{ color: 'var(--scheme-fg-muted)' }} />
                    New note
                </Link>
            </div>

            <div
                className="mt-1 flex items-center justify-between border-t px-4 pb-1 pt-2"
                style={{ borderColor: 'var(--glass-border)' }}
            >
                <span
                    className="text-xs font-medium uppercase tracking-wider"
                    style={{ color: 'var(--scheme-fg-muted)' }}
                >
                    Folders
                </span>
                <button
                    type="button"
                    onClick={() => setCreating(true)}
                    className="rounded p-0.5 hover:bg-[var(--scheme-accent-subtle)]"
                    style={{ color: 'var(--scheme-fg-muted)' }}
                    title="New folder"
                >
                    <FolderPlus className="h-3.5 w-3.5" />
                </button>
            </div>

            {creating && (
                <form onSubmit={submit} className="px-3 pb-2">
                    <input
                        autoFocus
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        onBlur={() => {
                            if (!name.trim()) setCreating(false);
                        }}
                        placeholder="Folder name"
                        className="w-full rounded-md border bg-background px-2 py-1 text-sm outline-none focus:border-primary"
                        style={{ borderColor: 'var(--glass-border)' }}
                    />
                </form>
            )}

            <div className="flex-1 overflow-y-auto pb-4">
                <FolderTree nodes={folderTree} activeNoteId={activeNoteId} />
            </div>
        </div>
    );
}
