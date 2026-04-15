import type { Auth } from '@/types/auth';
import type { FolderNode } from '@/types/models';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            folderTree: FolderNode[];
            flash?: { success?: string; error?: string };
            [key: string]: unknown;
        };
    }
}
