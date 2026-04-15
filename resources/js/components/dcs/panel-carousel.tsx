import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useRef, type ReactNode } from 'react';
import { useTheme } from '@/contexts/theme-context';

type PanelDef = {
    label: string;
    content: ReactNode;
};

type Props = {
    panels: PanelDef[];
    activePanel: number;
    onPanelChange: (index: number) => void;
    side: 'left' | 'right';
    headerSlot?: ReactNode;
};

export default function PanelCarousel({ panels, activePanel, onPanelChange, side, headerSlot }: Props) {
    const { carouselMode } = useTheme();
    const len = panels.length;
    const prev = (activePanel - 1 + len) % len;
    const next = (activePanel + 1) % len;
    const trackRef = useRef<HTMLDivElement>(null);
    const panelRefs = useRef<(HTMLDivElement | null)[]>([]);
    const prevModeRef = useRef(carouselMode);

    useEffect(() => {
        const track = trackRef.current;
        if (!track) return;
        const panelEls = panelRefs.current;

        if (prevModeRef.current !== carouselMode) {
            track.style.transition = 'none';
            panelEls.forEach((p) => {
                if (p) p.style.transition = 'none';
            });

            if (carouselMode === 'fade') {
                track.style.display = 'grid';
                track.style.transform = '';
                panelEls.forEach((p, i) => {
                    if (!p) return;
                    p.style.gridArea = '1 / 1';
                    p.style.width = '';
                    p.style.flexShrink = '';
                    p.style.opacity = i === activePanel ? '1' : '0';
                    p.style.pointerEvents = i === activePanel ? 'auto' : 'none';
                });
            } else {
                track.style.display = 'flex';
                track.style.transform = `translateX(-${activePanel * 100}%)`;
                panelEls.forEach((p) => {
                    if (!p) return;
                    p.style.gridArea = '';
                    p.style.width = '100%';
                    p.style.flexShrink = '0';
                    p.style.opacity = '1';
                    p.style.pointerEvents = 'auto';
                });
            }

            // Force reflow
            void track.offsetHeight;
            track.style.transition = '';
            panelEls.forEach((p) => {
                if (p) p.style.transition = '';
            });

            prevModeRef.current = carouselMode;
        }
    }, [carouselMode, activePanel]);

    useEffect(() => {
        const track = trackRef.current;
        if (!track) return;
        const panelEls = panelRefs.current;

        if (carouselMode === 'fade') {
            panelEls.forEach((p, i) => {
                if (!p) return;
                p.style.opacity = i === activePanel ? '1' : '0';
                p.style.pointerEvents = i === activePanel ? 'auto' : 'none';
            });
        } else {
            track.style.transform = `translateX(-${activePanel * 100}%)`;
        }
    }, [activePanel, carouselMode]);

    const isFade = carouselMode === 'fade';

    const carouselNav = (
        <div className="flex items-center gap-1.5">
            <button
                onClick={() => onPanelChange(prev)}
                className="rounded p-0.5 transition-colors hover:bg-background"
                style={{ color: 'var(--scheme-fg-muted)' }}
                aria-label="Previous panel"
            >
                <ChevronLeft className="h-5 w-5" />
            </button>
            <div className="flex items-center gap-1.5">
                {panels.map((p, i) => (
                    <button
                        key={p.label}
                        onClick={() => onPanelChange(i)}
                        className="transition-all"
                        style={{
                            width: i === activePanel ? 24 : 9,
                            height: 9,
                            borderRadius: 5,
                            backgroundColor: i === activePanel ? 'var(--scheme-accent)' : 'var(--scheme-fg-muted)',
                            opacity: i === activePanel ? 1 : 0.4,
                        }}
                        aria-label={p.label}
                    />
                ))}
            </div>
            <button
                onClick={() => onPanelChange(next)}
                className="rounded p-0.5 transition-colors hover:bg-background"
                style={{ color: 'var(--scheme-fg-muted)' }}
                aria-label="Next panel"
            >
                <ChevronRight className="h-5 w-5" />
            </button>
        </div>
    );

    return (
        <>
            <div
                className={`flex h-[var(--topnav-height)] shrink-0 items-center border-b gap-1 ${
                    side === 'left' ? 'justify-start pl-[3.75rem]' : 'justify-end pr-[3.75rem]'
                }`}
                style={{ borderColor: 'var(--glass-border)' }}
            >
                {side === 'left' && headerSlot}
                {carouselNav}
                {side === 'right' && headerSlot}
            </div>

            <div className="relative flex-1 overflow-hidden">
                <div
                    ref={trackRef}
                    className="h-full"
                    style={{
                        display: isFade ? 'grid' : 'flex',
                        transition: isFade ? 'none' : 'transform 0.3s ease-in-out',
                        transform: isFade ? undefined : `translateX(-${activePanel * 100}%)`,
                    }}
                >
                    {panels.map((p, i) => {
                        const displayName = p.label.replace(/^[LR]\d+:\s*/, '');
                        const active = i === activePanel;
                        return (
                            <div
                                key={p.label}
                                ref={(el) => {
                                    panelRefs.current[i] = el;
                                }}
                                className="flex h-full flex-col"
                                style={{
                                    ...(isFade
                                        ? {
                                              gridArea: '1 / 1',
                                              opacity: active ? 1 : 0,
                                              pointerEvents: active ? 'auto' : 'none',
                                              transition: 'opacity 0.3s ease-in-out',
                                          }
                                        : {
                                              width: '100%',
                                              flexShrink: 0,
                                              opacity: 1,
                                              pointerEvents: 'auto',
                                          }),
                                }}
                            >
                                <div
                                    className="shrink-0 border-b px-4 py-2 text-center"
                                    style={{
                                        borderColor: 'var(--glass-border)',
                                        background: 'color-mix(in oklch, var(--scheme-accent) 4%, transparent)',
                                    }}
                                    title={p.label}
                                >
                                    <h2
                                        className="text-sm font-bold"
                                        style={{ color: 'var(--scheme-fg-primary)' }}
                                    >
                                        {displayName}
                                    </h2>
                                </div>
                                <div className="flex-1 overflow-y-auto">{p.content}</div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </>
    );
}
