import { Head, router } from '@inertiajs/react';
import type { Role } from '@/types/auth';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: Role | null;
    email_verified_at: string | null;
    created_at: string | null;
};

type PaginatedUsers = {
    data: UserRow[];
    current_page: number;
    last_page: number;
};

type Props = {
    users: PaginatedUsers;
    roles: Role[];
};

export default function AdminUsersIndex({ users, roles }: Props) {
    const changeRole = (user: UserRow, role: Role) => {
        router.patch(`/admin/users/${user.id}`, { role }, { preserveScroll: true });
    };

    const deleteUser = (user: UserRow) => {
        if (!window.confirm(`Delete ${user.name}?`)) return;
        router.delete(`/admin/users/${user.id}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Users" />
            <div className="mx-auto max-w-5xl px-6 py-8">
                <h1 className="mb-6 text-2xl font-semibold">Users</h1>
                <table className="w-full text-sm">
                    <thead className="text-left text-xs uppercase text-muted-foreground">
                        <tr>
                            <th className="py-2">Name</th>
                            <th className="py-2">Email</th>
                            <th className="py-2">Role</th>
                            <th className="py-2">Verified</th>
                            <th className="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.data.map((user) => (
                            <tr key={user.id} className="border-t">
                                <td className="py-2">{user.name}</td>
                                <td className="py-2 text-muted-foreground">{user.email}</td>
                                <td className="py-2">
                                    <select
                                        value={user.role ?? 'user'}
                                        onChange={(e) => changeRole(user, e.target.value as Role)}
                                        className="rounded border bg-background px-2 py-1"
                                    >
                                        {roles.map((r) => (
                                            <option key={r} value={r}>
                                                {r}
                                            </option>
                                        ))}
                                    </select>
                                </td>
                                <td className="py-2 text-xs text-muted-foreground">
                                    {user.email_verified_at ? 'yes' : 'no'}
                                </td>
                                <td className="py-2 text-right">
                                    <button
                                        type="button"
                                        onClick={() => deleteUser(user)}
                                        className="rounded px-2 py-1 text-xs text-destructive hover:bg-destructive/10"
                                    >
                                        delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {users.last_page > 1 && (
                    <div className="mt-4 text-xs text-muted-foreground">
                        Page {users.current_page} of {users.last_page}
                    </div>
                )}
            </div>
        </>
    );
}
