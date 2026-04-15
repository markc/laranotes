import { Link } from '@inertiajs/react';
import { FileText, Lock, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useDebounce } from '@/hooks/use-debounce';
import type { SearchResult } from '@/types/models';

export function SearchBar() {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const debounced = useDebounce(query, 200);

    useEffect(() => {
        if (!debounced.trim()) {
            // Stale results stay in state but are hidden by the
            // {open && query.trim() && ...} guard in the render path.
            return;
        }

        let cancelled = false;
        fetch(`/notes/search?q=${encodeURIComponent(debounced)}`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => r.json())
            .then((data) => {
                if (!cancelled) {
                    setResults(data.results ?? []);
                }
            })
            .catch(() => {});

        return () => {
            cancelled = true;
        };
    }, [debounced]);

    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(e.target as Node)
            ) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', handler);

        return () => document.removeEventListener('mousedown', handler);
    }, []);

    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const input = containerRef.current?.querySelector('input');
                input?.focus();
                setOpen(true);
            }
        };
        window.addEventListener('keydown', handler);

        return () => window.removeEventListener('keydown', handler);
    }, []);

    return (
        <div ref={containerRef} className="relative">
            <div className="flex items-center gap-2 rounded-md border bg-background px-2 py-1.5">
                <Search className="h-4 w-4 text-muted-foreground" />
                <input
                    type="text"
                    value={query}
                    onChange={(e) => {
                        setQuery(e.target.value);
                        setOpen(true);
                    }}
                    onFocus={() => setOpen(true)}
                    placeholder="Search notes… (Ctrl+K)"
                    className="flex-1 bg-transparent text-sm outline-none"
                />
            </div>

            {open && query.trim() && (
                <div className="absolute top-full right-0 left-0 z-50 mt-1 max-h-[24rem] overflow-auto rounded-md border bg-popover shadow-lg">
                    {results.length === 0 ? (
                        <div className="px-3 py-4 text-center text-sm text-muted-foreground">
                            No results
                        </div>
                    ) : (
                        <ul>
                            {results.map((r) => (
                                <li key={r.id}>
                                    <Link
                                        href={`/notes/${r.id}/edit`}
                                        onClick={() => setOpen(false)}
                                        className="block border-b px-3 py-2 text-sm last:border-b-0 hover:bg-accent"
                                    >
                                        <div className="flex items-center gap-2">
                                            <FileText className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                            <span className="truncate font-medium">
                                                {r.title}
                                            </span>
                                            {r.is_private && (
                                                <Lock className="h-3 w-3 text-muted-foreground" />
                                            )}
                                            {r.folder && (
                                                <span className="ml-auto shrink-0 text-xs text-muted-foreground">
                                                    {r.folder.name}
                                                </span>
                                            )}
                                        </div>
                                        {r.snippet && (
                                            <div className="mt-1 line-clamp-2 pl-5 text-xs text-muted-foreground">
                                                {r.snippet}
                                            </div>
                                        )}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </div>
    );
}
