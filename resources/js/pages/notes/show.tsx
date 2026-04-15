import { Head, Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { MarkdownPreview } from '@/components/markdown-preview';
import { Button } from '@/components/ui/button';
import type { Note } from '@/types/models';

export default function ShowNote({ note }: { note: Note }) {
    return (
        <>
            <Head title={note.title} />
            <div className="mx-auto max-w-3xl px-6 py-8">
                <div className="mb-6 flex items-start justify-between gap-4">
                    <h1 className="text-3xl font-bold">{note.title}</h1>
                    <Button asChild variant="outline" size="sm">
                        <Link href={`/notes/${note.id}/edit`}>
                            <Pencil className="mr-1 h-3.5 w-3.5" /> Edit
                        </Link>
                    </Button>
                </div>
                <MarkdownPreview content={note.body} />
            </div>
        </>
    );
}
