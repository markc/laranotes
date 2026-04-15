import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { FolderLite } from '@/types/models';

type Props = {
    folder_id: number | null;
    folders: FolderLite[];
};

export default function CreateNote({ folder_id, folders }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        body: '',
        folder_id: folder_id,
        is_private: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/notes');
    };

    return (
        <>
            <Head title="New note" />
            <div className="mx-auto max-w-xl p-8">
                <h1 className="mb-6 text-2xl font-semibold">New note</h1>
                <form onSubmit={submit} className="flex flex-col gap-4">
                    <div>
                        <label className="mb-1 block text-sm font-medium">
                            Title
                        </label>
                        <input
                            autoFocus
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="w-full rounded-md border bg-background px-3 py-2"
                            placeholder="Note title"
                            required
                        />
                        {errors.title && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.title}
                            </p>
                        )}
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium">
                            Folder
                        </label>
                        <select
                            value={data.folder_id ?? ''}
                            onChange={(e) =>
                                setData(
                                    'folder_id',
                                    e.target.value
                                        ? parseInt(e.target.value, 10)
                                        : null,
                                )
                            }
                            className="w-full rounded-md border bg-background px-3 py-2"
                        >
                            <option value="">Unfiled</option>
                            {folders.map((f) => (
                                <option key={f.id} value={f.id}>
                                    {f.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={data.is_private}
                            onChange={(e) =>
                                setData('is_private', e.target.checked)
                            }
                        />
                        Private (only visible to me)
                    </label>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Create
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/dashboard')}
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
