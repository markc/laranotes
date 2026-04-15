export type Role = 'admin' | 'moderator' | 'user' | 'viewer';

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    role?: Role;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

// Advisory hints for UI gating — not authoritative. Server policies are
// the source of truth; these flags exist so components can hide buttons
// without duplicating role logic on the client.
export type CanHints = {
    createNotes: boolean;
    createFolders: boolean;
    moderate: boolean;
    manageUsers: boolean;
};

export type Auth = {
    user: User | null;
    role: Role | null;
    canHints: CanHints | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
