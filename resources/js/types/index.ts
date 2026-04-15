import type { FolderNode } from './models';

export type * from './auth';
export type * from './navigation';
export type * from './ui';
export type * from './models';

export interface PageProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
        } | null;
    };
    name: string;
    sidebarOpen: boolean;
    folderTree: FolderNode[];
    flash?: {
        success?: string;
        error?: string;
    };
}
