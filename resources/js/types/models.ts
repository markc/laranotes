export type UserLite = {
    id: number;
    name: string;
};

export type NoteLite = {
    id: number;
    title: string;
    slug: string;
    is_private: boolean;
    user_id: number;
    updated_at: string | null;
};

export type FolderNode = {
    id: number;
    name: string;
    slug: string;
    parent_id: number | null;
    is_private: boolean;
    user_id: number;
    notes: NoteLite[];
    children: FolderNode[];
};

export type FolderLite = {
    id: number;
    name: string;
    parent_id: number | null;
};

export type Note = {
    id: number;
    title: string;
    slug: string;
    body: string;
    is_private: boolean;
    folder_id: number | null;
    folder: { id: number; name: string } | null;
    user_id: number;
    author: UserLite | null;
    last_editor: UserLite | null;
    created_at: string | null;
    updated_at: string | null;
};

export type RecentNote = {
    id: number;
    title: string;
    slug: string;
    is_private: boolean;
    folder: { id: number; name: string } | null;
    last_editor: UserLite | null;
    updated_at: string | null;
};

export type SearchResult = {
    id: number;
    title: string;
    slug: string;
    folder: { id: number; name: string } | null;
    snippet: string;
    is_private: boolean;
    updated_at: string | null;
};
