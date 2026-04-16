import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import type { Role } from '@/types/auth';

type Invite = {
    id: number;
    email: string;
    role: Role | null;
    token: string;
    accept_url: string;
    inviter: { id: number; name: string } | null;
    expires_at: string | null;
    accepted_at: string | null;
    revoked_at: string | null;
    is_claimable: boolean;
};

type Props = {
    invites: Invite[];
    invitable_roles: Role[];
};

export default function InvitesIndex({ invites, invitable_roles }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        role: invitable_roles[0] ?? 'user',
    });
    const [copiedToken, setCopiedToken] = useState<string | null>(null);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/invites', {
            preserveScroll: true,
            onSuccess: () => reset('email'),
        });
    };

    const revoke = (invite: Invite) => {
        if (!window.confirm(`Revoke invite for ${invite.email}?`)) {
            return;
        }

        router.delete(`/invites/${invite.id}`, { preserveScroll: true });
    };

    const copy = async (invite: Invite) => {
        try {
            await navigator.clipboard.writeText(invite.accept_url);
            setCopiedToken(invite.token);
            window.setTimeout(() => setCopiedToken(null), 2000);
        } catch {
            window.prompt('Copy the invite URL:', invite.accept_url);
        }
    };

    const status = (invite: Invite) => {
        if (invite.accepted_at) {
            return 'accepted';
        }

        if (invite.revoked_at) {
            return 'revoked';
        }

        if (!invite.is_claimable) {
            return 'expired';
        }

        return 'pending';
    };

    return (
        <>
            <Head title="Invites" />
            <div className="mx-auto max-w-4xl px-6 py-8">
                <h1 className="mb-6 text-2xl font-semibold">Invites</h1>

                <form
                    onSubmit={submit}
                    className="mb-8 flex flex-col gap-3 rounded-md border p-4"
                >
                    <h2 className="text-sm font-semibold">Create invite</h2>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="email@example.com"
                            required
                            className="flex-1 rounded border bg-background px-2 py-1.5 text-sm"
                        />
                        <select
                            value={data.role}
                            onChange={(e) =>
                                setData('role', e.target.value as Role)
                            }
                            className="rounded border bg-background px-2 py-1.5 text-sm"
                        >
                            {invitable_roles.map((r) => (
                                <option key={r} value={r}>
                                    {r}
                                </option>
                            ))}
                        </select>
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded bg-primary px-4 py-1.5 text-sm text-primary-foreground hover:opacity-90 disabled:opacity-50"
                        >
                            Invite
                        </button>
                    </div>
                    {errors.email && (
                        <p className="text-xs text-destructive">
                            {errors.email}
                        </p>
                    )}
                    {errors.role && (
                        <p className="text-xs text-destructive">
                            {errors.role}
                        </p>
                    )}
                </form>

                <table className="w-full text-sm">
                    <thead className="text-left text-xs text-muted-foreground uppercase">
                        <tr>
                            <th className="py-2">Email</th>
                            <th className="py-2">Role</th>
                            <th className="py-2">Status</th>
                            <th className="py-2">Invited by</th>
                            <th className="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {invites.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="py-4 text-center text-muted-foreground"
                                >
                                    No invites yet.
                                </td>
                            </tr>
                        )}
                        {invites.map((invite) => (
                            <tr key={invite.id} className="border-t">
                                <td className="py-2">{invite.email}</td>
                                <td className="py-2">{invite.role}</td>
                                <td className="py-2 text-xs">
                                    {status(invite)}
                                </td>
                                <td className="py-2 text-xs text-muted-foreground">
                                    {invite.inviter?.name ?? '—'}
                                </td>
                                <td className="flex justify-end gap-2 py-2">
                                    {invite.is_claimable && (
                                        <button
                                            type="button"
                                            onClick={() => copy(invite)}
                                            className="rounded px-2 py-1 text-xs hover:bg-accent"
                                        >
                                            {copiedToken === invite.token
                                                ? 'copied'
                                                : 'copy link'}
                                        </button>
                                    )}
                                    {invite.is_claimable && (
                                        <button
                                            type="button"
                                            onClick={() => revoke(invite)}
                                            className="rounded px-2 py-1 text-xs text-destructive hover:bg-destructive/10"
                                        >
                                            revoke
                                        </button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}
