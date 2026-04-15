import AppDualSidebarLayout from '@/layouts/app/app-dual-sidebar-layout';
import type { AppLayoutProps } from '@/types';

export default function AppLayout({ children }: AppLayoutProps) {
    return <AppDualSidebarLayout>{children}</AppDualSidebarLayout>;
}
