import { usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useEffect, type ReactNode } from 'react';
import { toast } from 'sonner';
import DcsSidebar from '@/components/dcs/dcs-sidebar';
import TopNav from '@/components/dcs/top-nav';
import { AboutPanel } from '@/components/dcs/panels/about-panel';
import { AccountPanel } from '@/components/dcs/panels/account-panel';
import { AppearancePanel } from '@/components/dcs/panels/appearance-panel';
import { FoldersPanel } from '@/components/dcs/panels/folders-panel';
import { SearchPanel } from '@/components/dcs/panels/search-panel';
import { ThemeProvider, useTheme } from '@/contexts/theme-context';

const leftPanels = [
    { label: 'L1: Folders', content: <FoldersPanel /> },
    { label: 'L2: About', content: <AboutPanel /> },
];

const rightPanels = [
    { label: 'R1: Search', content: <SearchPanel /> },
    { label: 'R2: Appearance', content: <AppearancePanel /> },
    { label: 'R3: Account', content: <AccountPanel /> },
];

function LayoutContent({ children }: { children: ReactNode }) {
    const { left, right, toggleSidebar } = useTheme();
    const { props, url } = usePage<{ flash?: { success?: string; error?: string } }>();

    useEffect(() => {
        if (props.flash?.success) toast.success(props.flash.success);
        if (props.flash?.error) toast.error(props.flash.error);
    }, [props.flash]);

    useEffect(() => {
        const onScroll = () =>
            document.body.classList.toggle('scrolled', window.scrollY > 0);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <div className="min-h-screen bg-background text-foreground">
            <button
                onClick={() => toggleSidebar('left')}
                className="fixed top-[0.625rem] left-3 z-50 rounded-lg p-1.5 transition-colors hover:text-[var(--scheme-accent)]"
                style={{
                    background: 'var(--glass)',
                    backdropFilter: 'blur(20px)',
                    WebkitBackdropFilter: 'blur(20px)',
                    border: '1px solid var(--glass-border)',
                    color: 'var(--scheme-fg-primary)',
                }}
                aria-label="Toggle left sidebar"
            >
                <Menu className="h-5 w-5" />
            </button>
            <button
                onClick={() => toggleSidebar('right')}
                className="fixed top-[0.625rem] right-3 z-50 rounded-lg p-1.5 transition-colors hover:text-[var(--scheme-accent)]"
                style={{
                    background: 'var(--glass)',
                    backdropFilter: 'blur(20px)',
                    WebkitBackdropFilter: 'blur(20px)',
                    border: '1px solid var(--glass-border)',
                    color: 'var(--scheme-fg-primary)',
                }}
                aria-label="Toggle right sidebar"
            >
                <Menu className="h-5 w-5" />
            </button>

            <DcsSidebar side="left" panels={leftPanels} />
            <DcsSidebar side="right" panels={rightPanels} />

            <TopNav />

            <div
                className="sidebar-slide"
                style={{
                    marginInlineStart: left.pinned ? 'var(--sidebar-width)' : undefined,
                    marginInlineEnd: right.pinned ? 'var(--sidebar-width)' : undefined,
                }}
            >
                <main key={url} className="page-fade-in">
                    {children}
                </main>
            </div>
        </div>
    );
}

export default function AppDualSidebarLayout({ children }: { children: ReactNode }) {
    return (
        <ThemeProvider>
            <LayoutContent>{children}</LayoutContent>
        </ThemeProvider>
    );
}
