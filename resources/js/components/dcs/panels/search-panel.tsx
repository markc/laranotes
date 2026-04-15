import { Link } from '@inertiajs/react';
import { FileText, Lock, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useDebounce } from '@/hooks/use-debounce';
import type { SearchResult } from '@/types/models';

export function SearchPanel() {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const debounced = useDebounce(query, 200);

    useEffect(() => {
        if (!debounced.trim()) {
            setResults([]);
            return;
        }
        let cancelled = false;
        fetch(`/notes/search?q=${encodeURIComponent(debounced)}`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => r.json())
            .then((data) => {
                if (!cancelled) setResults(data.results ?? []);
            })
            .catch(() => {});
        return () => {
            cancelled = true;
        };
    }, [debounced]);

    return (
        <div className="flex h-full flex-col">
            <div className="px-3 py-3">
                <div
                    className="flex items-center gap-2 rounded-md border bg-background px-2 py-1.5"
                    style={{ borderColor: 'var(--glass-border)' }}
                >
                    <Search className="h-4 w-4" style={{ color: 'var(--scheme-fg-muted)' }} />
                    <input
                        autoFocus
                        type="text"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Search notes…"
                        className="flex-1 bg-transparent text-sm outline-none"
                    />
                </div>
            </div>

            <div className="flex-1 overflow-y-auto px-2 pb-3">
                {query.trim() === '' && (
                    <p
                        className="px-2 text-xs"
                        style={{ color: 'var(--scheme-fg-muted)' }}
                    >
                        Type to search titles and bodies.
                    </p>
                )}
                {query.trim() !== '' && results.length === 0 && (
                    <p
                        className="px-2 text-xs"
                        style={{ color: 'var(--scheme-fg-muted)' }}
                    >
                        No results
                    </p>
                )}
                <ul className="flex flex-col">
                    {results.map((r) => (
                        <li key={r.id}>
                            <Link
                                href={`/notes/${r.id}/edit`}
                                className="block rounded-md px-2 py-2 text-sm hover:bg-[var(--scheme-accent-subtle)]"
                            >
                                <div className="flex items-center gap-1.5">
                                    <FileText className="h-3.5 w-3.5 shrink-0" style={{ color: 'var(--scheme-fg-muted)' }} />
                                    <span className="truncate font-medium">{r.title}</span>
                                    {r.is_private && <Lock className="h-3 w-3 shrink-0" style={{ color: 'var(--scheme-fg-muted)' }} />}
                                </div>
                                {r.folder && (
                                    <div className="mt-0.5 pl-5 text-xs" style={{ color: 'var(--scheme-fg-muted)' }}>
                                        {r.folder.name}
                                    </div>
                                )}
                                {r.snippet && (
                                    <div className="mt-0.5 line-clamp-2 pl-5 text-xs" style={{ color: 'var(--scheme-fg-muted)' }}>
                                        {r.snippet}
                                    </div>
                                )}
                            </Link>
                        </li>
                    ))}
                </ul>
            </div>
        </div>
    );
}
