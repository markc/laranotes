import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react';

export type ColorScheme = 'crimson' | 'stone' | 'ocean' | 'forest' | 'sunset';
export type ThemeMode = 'light' | 'dark';
export type CarouselMode = 'slide' | 'fade';

type SidebarState = {
    open: boolean;
    pinned: boolean;
    panel: number;
};

type ThemeState = {
    theme: ThemeMode;
    scheme: ColorScheme;
    carouselMode: CarouselMode;
    left: SidebarState;
    right: SidebarState;
    sidebarWidth: number;
};

type ThemeContextValue = ThemeState & {
    noPadding: boolean;
    setNoPadding: (value: boolean) => void;
    toggleTheme: () => void;
    setScheme: (scheme: ColorScheme) => void;
    setCarouselMode: (mode: CarouselMode) => void;
    toggleSidebar: (side: 'left' | 'right') => void;
    pinSidebar: (side: 'left' | 'right') => void;
    closeSidebars: () => void;
    setPanel: (side: 'left' | 'right', index: number) => void;
    setSidebarWidth: (width: number) => void;
};

const STORAGE_KEY = 'laranotes-state';

const defaults: ThemeState = {
    theme: 'dark',
    scheme: 'ocean',
    carouselMode: 'slide',
    left: { open: true, pinned: true, panel: 0 },
    right: { open: false, pinned: false, panel: 0 },
    sidebarWidth: 280,
};

function clampPanel(n: number, max: number): number {
    return Math.max(0, Math.min(max, n));
}

function loadState(): ThemeState {
    if (typeof window === 'undefined') return defaults;
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return defaults;
        const parsed = JSON.parse(raw);
        return {
            theme: parsed.theme || defaults.theme,
            scheme: parsed.scheme || defaults.scheme,
            carouselMode: parsed.carouselMode || defaults.carouselMode,
            left: {
                open: parsed.leftOpen ?? defaults.left.open,
                pinned: parsed.leftPinned ?? defaults.left.pinned,
                panel: clampPanel(parsed.leftPanel ?? 0, 4),
            },
            right: {
                open: parsed.rightOpen ?? defaults.right.open,
                pinned: parsed.rightPinned ?? defaults.right.pinned,
                panel: clampPanel(parsed.rightPanel ?? 0, 4),
            },
            sidebarWidth: parsed.sidebarWidth ?? defaults.sidebarWidth,
        };
    } catch {
        return defaults;
    }
}

function saveState(state: ThemeState) {
    localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({
            theme: state.theme,
            scheme: state.scheme,
            carouselMode: state.carouselMode,
            leftOpen: state.left.open,
            leftPinned: state.left.pinned,
            leftPanel: state.left.panel,
            rightOpen: state.right.open,
            rightPinned: state.right.pinned,
            rightPanel: state.right.panel,
            sidebarWidth: state.sidebarWidth,
        }),
    );
}

function applyThemeToDOM(theme: ThemeMode) {
    const html = document.documentElement;
    html.classList.toggle('dark', theme === 'dark');
    html.style.colorScheme = theme;
}

function applySchemeToDOM(scheme: ColorScheme) {
    const html = document.documentElement;
    ['stone', 'ocean', 'forest', 'sunset'].forEach((s) => html.classList.remove(`scheme-${s}`));
    if (scheme !== 'crimson') {
        html.classList.add(`scheme-${scheme}`);
    }
}

const ThemeContext = createContext<ThemeContextValue | null>(null);

export function ThemeProvider({ children }: { children: ReactNode }) {
    const [state, setState] = useState<ThemeState>(loadState);
    const [noPadding, setNoPadding] = useState(false);

    useEffect(() => {
        applyThemeToDOM(state.theme);
        applySchemeToDOM(state.scheme);
        document.documentElement.style.setProperty('--sidebar-width', `${state.sidebarWidth}px`);
        saveState(state);
    }, [state]);

    useEffect(() => {
        const mq = window.matchMedia('(min-width: 1280px)');
        const handler = (e: MediaQueryListEvent) => {
            if (!e.matches) {
                setState((prev) => ({
                    ...prev,
                    left: { ...prev.left, open: false, pinned: false },
                    right: { ...prev.right, open: false, pinned: false },
                }));
            }
        };
        mq.addEventListener('change', handler);
        return () => mq.removeEventListener('change', handler);
    }, []);

    const toggleTheme = useCallback(() => {
        setState((prev) => ({ ...prev, theme: prev.theme === 'dark' ? 'light' : 'dark' }));
    }, []);

    const setScheme = useCallback((scheme: ColorScheme) => {
        setState((prev) => ({ ...prev, scheme }));
    }, []);

    const setCarouselMode = useCallback((carouselMode: CarouselMode) => {
        setState((prev) => ({ ...prev, carouselMode }));
    }, []);

    const toggleSidebar = useCallback((side: 'left' | 'right') => {
        setState((prev) => {
            const current = prev[side];
            if (current.open) {
                return { ...prev, [side]: { ...current, open: false, pinned: false } };
            }
            return { ...prev, [side]: { ...current, open: true } };
        });
    }, []);

    const pinSidebar = useCallback((side: 'left' | 'right') => {
        setState((prev) => {
            const current = prev[side];
            const pinning = !current.pinned;
            return {
                ...prev,
                [side]: { ...current, open: pinning, pinned: pinning },
            };
        });
    }, []);

    const closeSidebars = useCallback(() => {
        setState((prev) => ({
            ...prev,
            left: prev.left.pinned ? prev.left : { ...prev.left, open: false, pinned: false },
            right: prev.right.pinned ? prev.right : { ...prev.right, open: false, pinned: false },
        }));
    }, []);

    const setPanel = useCallback((side: 'left' | 'right', index: number) => {
        setState((prev) => ({
            ...prev,
            [side]: { ...prev[side], panel: clampPanel(index, 4) },
        }));
    }, []);

    const setSidebarWidth = useCallback((width: number) => {
        setState((prev) => ({ ...prev, sidebarWidth: Math.max(220, Math.min(500, width)) }));
    }, []);

    return (
        <ThemeContext.Provider
            value={{
                ...state,
                noPadding,
                setNoPadding,
                toggleTheme,
                setScheme,
                setCarouselMode,
                toggleSidebar,
                pinSidebar,
                closeSidebars,
                setPanel,
                setSidebarWidth,
            }}
        >
            {children}
        </ThemeContext.Provider>
    );
}

export function useTheme(): ThemeContextValue {
    const ctx = useContext(ThemeContext);
    if (!ctx) throw new Error('useTheme must be used within ThemeProvider');
    return ctx;
}
