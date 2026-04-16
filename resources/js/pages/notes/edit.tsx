import { Head, router } from '@inertiajs/react';
import { Columns, Eye, Lock, LockOpen, Pencil, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { MarkdownEditor } from '@/components/markdown-editor';
import { MarkdownPreview } from '@/components/markdown-preview';
import { Button } from '@/components/ui/button';
import { useDebounce } from '@/hooks/use-debounce';
import { cn } from '@/lib/utils';
import type { FolderLite, Note } from '@/types/models';

type Props = {
    note: Note;
    folders: FolderLite[];
};

type ViewMode = 'split' | 'editor' | 'preview';

export default function EditNote({ note, folders }: Props) {
    const [title, setTitle] = useState(note.title);
    const [body, setBody] = useState(note.body ?? '');
    const [isPrivate, setIsPrivate] = useState(note.is_private);
    const [folderId, setFolderId] = useState<number | null>(note.folder_id);
    const [status, setStatus] = useState<'idle' | 'saving' | 'saved'>('idle');
    const [view, setView] = useState<ViewMode>('split');
    const initialized = useRef(false);

    const readOnly = !note.can_edit;
    const debouncedTitle = useDebounce(title, 700);
    const debouncedBody = useDebounce(body, 700);

    // Auto-save on debounced changes (skip in read-only mode)
    useEffect(() => {
        if (!initialized.current) {
            initialized.current = true;

            return;
        }

        if (readOnly) {
return;
}

        setStatus('saving');
        router.put(
            `/notes/${note.id}`,
            {
                title: debouncedTitle,
                body: debouncedBody,
                is_private: isPrivate,
                folder_id: folderId,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => setStatus('saved'),
                onError: () => setStatus('idle'),
            },
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debouncedTitle, debouncedBody, isPrivate, folderId]);

    // Keyboard shortcuts
    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if (readOnly) {
return;
}

            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                setStatus('saving');
                router.put(
                    `/notes/${note.id}`,
                    { title, body, is_private: isPrivate, folder_id: folderId },
                    {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: () => setStatus('saved'),
                    },
                );
            }
        };
        window.addEventListener('keydown', handler);

        return () => window.removeEventListener('keydown', handler);
    }, [note.id, title, body, isPrivate, folderId, readOnly]);

    const handleDelete = () => {
        if (window.confirm('Delete this note?')) {
            router.delete(`/notes/${note.id}`);
        }
    };

    return (
        <>
            <Head title={title || 'Untitled'} />
            <div className="flex h-[calc(100vh-var(--topnav-height))] flex-col">
                {readOnly && (
                    <div
                        className="border-b bg-muted/40 px-4 py-2 text-xs text-muted-foreground"
                        role="status"
                    >
                        Read-only — this note is owned by{' '}
                        <span className="font-medium">
                            {note.author?.name ?? 'someone else'}
                        </span>
                        . You can view but not modify it.
                    </div>
                )}
                <div className="flex flex-wrap items-center gap-2 border-b px-4 py-3">
                    <input
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        placeholder="Untitled"
                        disabled={readOnly}
                        className="min-w-[12rem] flex-1 bg-transparent text-xl font-semibold outline-none disabled:cursor-not-allowed disabled:opacity-70"
                    />

                    <select
                        value={folderId ?? ''}
                        onChange={(e) =>
                            setFolderId(
                                e.target.value
                                    ? parseInt(e.target.value, 10)
                                    : null,
                            )
                        }
                        disabled={readOnly}
                        className="rounded-md border bg-background px-2 py-1 text-sm disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <option value="">Unfiled</option>
                        {folders.map((f) => (
                            <option key={f.id} value={f.id}>
                                {f.name}
                            </option>
                        ))}
                    </select>

                    <div className="flex items-center gap-0.5 rounded-md border p-0.5">
                        <ViewButton
                            active={view === 'editor'}
                            onClick={() => setView('editor')}
                            icon={<Pencil className="h-3.5 w-3.5" />}
                            label="Editor"
                        />
                        <ViewButton
                            active={view === 'split'}
                            onClick={() => setView('split')}
                            icon={<Columns className="h-3.5 w-3.5" />}
                            label="Split"
                        />
                        <ViewButton
                            active={view === 'preview'}
                            onClick={() => setView('preview')}
                            icon={<Eye className="h-3.5 w-3.5" />}
                            label="Preview"
                        />
                    </div>

                    {!readOnly && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setIsPrivate(!isPrivate)}
                            title={isPrivate ? 'Make shared' : 'Make private'}
                        >
                            {isPrivate ? (
                                <Lock className="h-3.5 w-3.5" />
                            ) : (
                                <LockOpen className="h-3.5 w-3.5" />
                            )}
                            <span className="ml-1 text-xs">
                                {isPrivate ? 'Private' : 'Shared'}
                            </span>
                        </Button>
                    )}

                    {note.can_delete && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleDelete}
                            className="text-destructive"
                        >
                            <Trash2 className="h-3.5 w-3.5" />
                        </Button>
                    )}

                    <span className="min-w-[4rem] text-right text-xs text-muted-foreground">
                        {status === 'saving'
                            ? 'Saving…'
                            : status === 'saved'
                              ? 'Saved'
                              : ''}
                    </span>
                </div>

                <div className="flex flex-1 overflow-hidden">
                    {(view === 'editor' || view === 'split') && (
                        <div
                            className={cn(
                                'h-full overflow-hidden border-r',
                                view === 'split' ? 'w-1/2' : 'flex-1',
                            )}
                        >
                            <MarkdownEditor
                                value={body}
                                onChange={setBody}
                                placeholder="Start writing…"
                                readOnly={readOnly}
                            />
                        </div>
                    )}
                    {(view === 'preview' || view === 'split') && (
                        <div
                            className={cn(
                                'h-full overflow-auto px-6 py-4',
                                view === 'split' ? 'w-1/2' : 'flex-1',
                            )}
                        >
                            <MarkdownPreview content={body} />
                        </div>
                    )}
                </div>

                <div className="border-t px-4 py-2 text-xs text-muted-foreground">
                    {note.author && (
                        <>
                            Author:{' '}
                            <span className="font-medium">
                                {note.author.name}
                            </span>
                        </>
                    )}
                    {note.last_editor &&
                        note.last_editor.id !== note.author?.id && (
                            <>
                                {' '}
                                · Last editor:{' '}
                                <span className="font-medium">
                                    {note.last_editor.name}
                                </span>
                            </>
                        )}
                    {note.updated_at && (
                        <>
                            {' '}
                            · Updated{' '}
                            {new Date(note.updated_at).toLocaleString()}
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

function ViewButton({
    active,
    onClick,
    icon,
    label,
}: {
    active: boolean;
    onClick: () => void;
    icon: React.ReactNode;
    label: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'flex items-center gap-1 rounded px-2 py-1 text-xs',
                active
                    ? 'bg-accent text-accent-foreground'
                    : 'text-muted-foreground hover:bg-accent/50',
            )}
            title={label}
        >
            {icon}
        </button>
    );
}
