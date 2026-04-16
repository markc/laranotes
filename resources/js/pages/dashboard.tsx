import { Head, Link } from '@inertiajs/react';
import { FileText, Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useCanHints } from '@/hooks/use-can-hints';
import type { RecentNote } from '@/types/models';

type Props = {
    recent_notes: RecentNote[];
};

export default function Dashboard({ recent_notes }: Props) {
    const canHints = useCanHints();

    return (
        <>
            <Head title="Dashboard" />
            <div className="mx-auto max-w-4xl px-6 py-8">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Recent notes</h1>
                    {canHints.createNotes && (
                        <Button asChild>
                            <Link href="/notes/create">New note</Link>
                        </Button>
                    )}
                </div>

                {recent_notes.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-12 text-center">
                        <p className="text-muted-foreground">No notes yet.</p>
                        {canHints.createNotes && (
                            <Button asChild className="mt-4">
                                <Link href="/notes/create">
                                    Create your first note
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <ul className="flex flex-col gap-2">
                        {recent_notes.map((note) => (
                            <li key={note.id}>
                                <Link
                                    href={`/notes/${note.id}/edit`}
                                    className="flex items-start justify-between gap-4 rounded-lg border bg-card px-4 py-3 transition-colors hover:border-primary/50 hover:bg-accent/30"
                                >
                                    <div className="flex min-w-0 flex-1 items-start gap-3">
                                        <FileText className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="truncate font-medium">
                                                    {note.title}
                                                </span>
                                                {note.is_private && (
                                                    <Lock className="h-3 w-3 shrink-0 text-muted-foreground" />
                                                )}
                                            </div>
                                            <div className="mt-0.5 text-xs text-muted-foreground">
                                                {note.folder?.name ?? 'Unfiled'}
                                                {note.last_editor && (
                                                    <>
                                                        {' '}
                                                        ·{' '}
                                                        {note.last_editor.name}
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    {note.updated_at && (
                                        <span className="shrink-0 text-xs text-muted-foreground">
                                            {new Date(
                                                note.updated_at,
                                            ).toLocaleDateString()}
                                        </span>
                                    )}
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
