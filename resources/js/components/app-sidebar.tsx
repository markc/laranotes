import { Link, router, usePage } from '@inertiajs/react';
import { FilePlus, FolderPlus, LayoutGrid, NotebookPen } from 'lucide-react';
import { useState } from 'react';
import { FolderTree } from '@/components/folder-tree';
import { NavUser } from '@/components/nav-user';
import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

export function AppSidebar() {
    const { props } = usePage();
    const folderTree = props.folderTree ?? [];

    const activeNoteId =
        typeof window !== 'undefined'
            ? (() => {
                  const match = window.location.pathname.match(/\/notes\/(\d+)/);
                  return match ? parseInt(match[1], 10) : null;
              })()
            : null;

    const [creatingFolder, setCreatingFolder] = useState(false);
    const [newFolderName, setNewFolderName] = useState('');

    const submitNewFolder = (e: React.FormEvent) => {
        e.preventDefault();
        const name = newFolderName.trim();
        if (!name) {
            setCreatingFolder(false);
            return;
        }
        router.post(
            '/folders',
            { name, parent_id: null },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCreatingFolder(false);
                    setNewFolderName('');
                },
            },
        );
    };

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                    <NotebookPen className="size-4" />
                                </div>
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-semibold">Laranotes</span>
                                    <span className="truncate text-xs text-muted-foreground">
                                        Markdown notes
                                    </span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarMenu className="px-2">
                    <SidebarMenuItem>
                        <SidebarMenuButton asChild>
                            <Link href="/dashboard">
                                <LayoutGrid />
                                <span>Dashboard</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton asChild>
                            <Link href="/notes/create">
                                <FilePlus />
                                <span>New note</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>

                <div className="mt-2 flex items-center justify-between px-4 pb-1 pt-2">
                    <span className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        Folders
                    </span>
                    <button
                        type="button"
                        onClick={() => setCreatingFolder(true)}
                        className="rounded p-0.5 text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        title="New folder"
                    >
                        <FolderPlus className="h-3.5 w-3.5" />
                    </button>
                </div>

                {creatingFolder && (
                    <form onSubmit={submitNewFolder} className="px-4 pb-2">
                        <input
                            autoFocus
                            value={newFolderName}
                            onChange={(e) => setNewFolderName(e.target.value)}
                            onBlur={() => {
                                if (!newFolderName.trim()) setCreatingFolder(false);
                            }}
                            placeholder="Folder name"
                            className="w-full rounded-md border border-sidebar-border bg-sidebar px-2 py-1 text-sm outline-none focus:border-primary"
                        />
                    </form>
                )}

                <FolderTree nodes={folderTree} activeNoteId={activeNoteId} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
