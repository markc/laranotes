import { Moon, Sun } from 'lucide-react';
import { useTheme, type ColorScheme } from '@/contexts/theme-context';

const schemes: { id: ColorScheme; label: string; hue: number }[] = [
    { id: 'crimson', label: 'Crimson', hue: 30 },
    { id: 'stone', label: 'Stone', hue: 60 },
    { id: 'sunset', label: 'Sunset', hue: 45 },
    { id: 'forest', label: 'Forest', hue: 150 },
    { id: 'ocean', label: 'Ocean', hue: 220 },
];

export function AppearancePanel() {
    const { theme, scheme, carouselMode, toggleTheme, setScheme, setCarouselMode, sidebarWidth, setSidebarWidth } =
        useTheme();

    return (
        <div className="flex flex-col gap-5 px-4 py-4 text-sm">
            <div>
                <h4
                    className="mb-2 text-xs font-bold uppercase tracking-wider"
                    style={{ color: 'var(--scheme-fg-muted)' }}
                >
                    Theme
                </h4>
                <button
                    onClick={toggleTheme}
                    className="flex w-full items-center justify-between rounded-md border px-3 py-2 text-sm transition-colors hover:bg-[var(--scheme-accent-subtle)]"
                    style={{ borderColor: 'var(--glass-border)' }}
                >
                    <span className="flex items-center gap-2">
                        {theme === 'dark' ? <Moon className="h-4 w-4" /> : <Sun className="h-4 w-4" />}
                        {theme === 'dark' ? 'Dark' : 'Light'}
                    </span>
                    <span className="text-xs" style={{ color: 'var(--scheme-fg-muted)' }}>
                        Toggle
                    </span>
                </button>
            </div>

            <div>
                <h4
                    className="mb-2 text-xs font-bold uppercase tracking-wider"
                    style={{ color: 'var(--scheme-fg-muted)' }}
                >
                    Colour scheme
                </h4>
                <div className="flex flex-wrap gap-2">
                    {schemes.map((s) => (
                        <button
                            key={s.id}
                            onClick={() => setScheme(s.id)}
                            className="flex flex-col items-center gap-1 rounded-md border p-2 text-xs transition-all"
                            style={{
                                borderColor:
                                    scheme === s.id ? 'var(--scheme-accent)' : 'var(--glass-border)',
                                background: scheme === s.id ? 'var(--scheme-accent-subtle)' : 'transparent',
                                minWidth: 60,
                            }}
                        >
                            <span
                                className="h-5 w-5 rounded-full"
                                style={{
                                    background: `oklch(60% 0.18 ${s.hue})`,
                                }}
                            />
                            <span>{s.label}</span>
                        </button>
                    ))}
                </div>
            </div>

            <div>
                <h4
                    className="mb-2 text-xs font-bold uppercase tracking-wider"
                    style={{ color: 'var(--scheme-fg-muted)' }}
                >
                    Panel transition
                </h4>
                <div className="flex gap-2">
                    <button
                        onClick={() => setCarouselMode('slide')}
                        className="flex-1 rounded-md border px-3 py-1.5 text-xs"
                        style={{
                            borderColor: carouselMode === 'slide' ? 'var(--scheme-accent)' : 'var(--glass-border)',
                            background:
                                carouselMode === 'slide' ? 'var(--scheme-accent-subtle)' : 'transparent',
                        }}
                    >
                        Slide
                    </button>
                    <button
                        onClick={() => setCarouselMode('fade')}
                        className="flex-1 rounded-md border px-3 py-1.5 text-xs"
                        style={{
                            borderColor: carouselMode === 'fade' ? 'var(--scheme-accent)' : 'var(--glass-border)',
                            background:
                                carouselMode === 'fade' ? 'var(--scheme-accent-subtle)' : 'transparent',
                        }}
                    >
                        Fade
                    </button>
                </div>
            </div>

            <div>
                <h4
                    className="mb-2 text-xs font-bold uppercase tracking-wider"
                    style={{ color: 'var(--scheme-fg-muted)' }}
                >
                    Sidebar width ({sidebarWidth}px)
                </h4>
                <input
                    type="range"
                    min={220}
                    max={400}
                    step={10}
                    value={sidebarWidth}
                    onChange={(e) => setSidebarWidth(parseInt(e.target.value, 10))}
                    className="scheme-range w-full"
                />
            </div>
        </div>
    );
}
