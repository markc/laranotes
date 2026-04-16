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
    siteDescription: string;
    defaultEditor: 'source' | 'wysiwyg';
    defaultTheme: 'light' | 'dark' | 'system';
    defaultScheme: 'crimson' | 'stone' | 'ocean' | 'forest' | 'sunset';
    sidebarOpen: boolean;
    folderTree: FolderNode[];
    flash?: {
        success?: string;
        error?: string;
    };
}
