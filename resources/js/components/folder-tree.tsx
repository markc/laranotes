import { Link, router, usePage } from '@inertiajs/react';
import { ChevronRight, FileText, Folder as FolderIcon, Lock, Plus } from 'lucide-react';
import { useState, type MouseEvent } from 'react';
import { cn } from '@/lib/utils';
import type { FolderNode, NoteLite } from '@/types/models';

type Props = {
    nodes: FolderNode[];
    activeNoteId?: number | null;
    depth?: number;
};

export function FolderTree({ nodes, activeNoteId, depth = 0 }: Props) {
    if (nodes.length === 0 && depth === 0) {
        return (
            <div className="px-3 py-2 text-xs text-muted-foreground">
                No folders yet. Create one below.
            </div>
        );
    }

    return (
        <ul className={cn('flex flex-col gap-0.5', depth === 0 && 'px-2')}>
            {nodes.map((node) => (
                <FolderTreeNode
                    key={node.id}
                    node={node}
                    activeNoteId={activeNoteId}
                    depth={depth}
                />
            ))}
        </ul>
    );
}

function FolderTreeNode({
    node,
    activeNoteId,
    depth,
}: {
    node: FolderNode;
    activeNoteId?: number | null;
    depth: number;
}) {
    const [open, setOpen] = useState(true);
    const hasChildren = node.children.length > 0;
    const hasNotes = node.notes.length > 0;

    const createNoteInFolder = (e: MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        router.visit(`/notes/create?folder_id=${node.id}`);
    };

    const renameFolder = (e: MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        const name = window.prompt('Rename folder', node.name);
        if (name && name.trim() && name.trim() !== node.name) {
            router.put(`/folders/${node.id}`, { name: name.trim() }, { preserveScroll: true });
        }
    };

    const deleteFolder = (e: MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (window.confirm(`Delete folder "${node.name}"? It must be empty.`)) {
            router.delete(`/folders/${node.id}`, { preserveScroll: true });
        }
    };

    return (
        <li>
            <div
                className="group flex items-center gap-1 rounded-md px-1.5 py-1 text-sm hover:bg-sidebar-accent"
                style={{ paddingInlineStart: `${depth * 0.75 + 0.375}rem` }}
            >
                <button
                    type="button"
                    onClick={() => setOpen(!open)}
                    className="flex flex-1 items-center gap-1.5 text-left"
                    onContextMenu={renameFolder}
                >
                    <ChevronRight
                        className={cn(
                            'h-3.5 w-3.5 shrink-0 text-muted-foreground transition-transform',
                            open && 'rotate-90',
                            !hasChildren && !hasNotes && 'opacity-40',
                        )}
                    />
                    <FolderIcon className="h-4 w-4 shrink-0 text-muted-foreground" />
                    <span className="truncate font-medium">{node.name}</span>
                </button>
                <button
                    type="button"
                    onClick={createNoteInFolder}
                    className="opacity-0 group-hover:opacity-100 rounded p-0.5 hover:bg-sidebar-accent-foreground/10"
                    title="New note in this folder"
                >
                    <Plus className="h-3.5 w-3.5" />
                </button>
                <button
                    type="button"
                    onClick={deleteFolder}
                    className="opacity-0 group-hover:opacity-100 rounded p-0.5 text-xs text-muted-foreground hover:bg-sidebar-accent-foreground/10"
                    title="Delete folder"
                >
                    ×
                </button>
            </div>
            {open && (
                <>
                    {hasNotes && (
                        <ul className="flex flex-col">
                            {node.notes.map((note) => (
                                <NoteLink
                                    key={note.id}
                                    note={note}
                                    activeNoteId={activeNoteId}
                                    depth={depth + 1}
                                />
                            ))}
                        </ul>
                    )}
                    {hasChildren && (
                        <FolderTree
                            nodes={node.children}
                            activeNoteId={activeNoteId}
                            depth={depth + 1}
                        />
                    )}
                </>
            )}
        </li>
    );
}

function NoteLink({
    note,
    activeNoteId,
    depth,
}: {
    note: NoteLite;
    activeNoteId?: number | null;
    depth: number;
}) {
    const currentUser = usePage().props.auth.user;
    const isActive = note.id === activeNoteId;

    return (
        <li>
            <Link
                href={`/notes/${note.id}/edit`}
                className={cn(
                    'flex items-center gap-1.5 rounded-md px-1.5 py-1 text-sm hover:bg-sidebar-accent',
                    isActive && 'bg-sidebar-accent font-medium text-sidebar-accent-foreground',
                )}
                style={{ paddingInlineStart: `${depth * 0.75 + 1.5}rem` }}
            >
                <FileText className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                <span className="truncate">{note.title}</span>
                {note.is_private && note.user_id === currentUser?.id && (
                    <Lock className="h-3 w-3 shrink-0 text-muted-foreground" />
                )}
            </Link>
        </li>
    );
}
