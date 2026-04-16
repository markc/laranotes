import { Head, useForm } from '@inertiajs/react';
import type {FormEvent} from 'react';
import type { Role } from '@/types/auth';

type Props = {
    invite: {
        token: string;
        email: string;
        role: Role | null;
        expires_at: string | null;
        inviter: { id: number; name: string } | null;
    };
};

export default function InviteAccept({ invite }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(`/invite/${invite.token}`);
    };

    return (
        <>
            <Head title="Accept invite" />
            <div className="flex min-h-screen items-center justify-center bg-background p-6">
                <div className="w-full max-w-md rounded-lg border p-6">
                    <h1 className="mb-2 text-xl font-semibold">
                        Join Laranotes
                    </h1>
                    <p className="mb-6 text-sm text-muted-foreground">
                        {invite.inviter
                            ? `${invite.inviter.name} invited you to join as `
                            : 'You have been invited to join as '}
                        <span className="font-medium">{invite.role}</span>. Set
                        a name and password to create your account.
                    </p>

                    <form
                        onSubmit={submit}
                        className="flex flex-col gap-4 text-sm"
                    >
                        <div>
                            <label className="mb-1 block text-xs text-muted-foreground">
                                Email
                            </label>
                            <input
                                type="email"
                                value={invite.email}
                                disabled
                                className="w-full rounded border bg-muted px-2 py-1.5"
                            />
                        </div>

                        <div>
                            <label className="mb-1 block text-xs text-muted-foreground">
                                Your name
                            </label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                required
                                autoFocus
                                className="w-full rounded border bg-background px-2 py-1.5"
                            />
                            {errors.name && (
                                <p className="mt-1 text-xs text-destructive">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="mb-1 block text-xs text-muted-foreground">
                                Password
                            </label>
                            <input
                                type="password"
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                required
                                autoComplete="new-password"
                                className="w-full rounded border bg-background px-2 py-1.5"
                            />
                            {errors.password && (
                                <p className="mt-1 text-xs text-destructive">
                                    {errors.password}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="mb-1 block text-xs text-muted-foreground">
                                Confirm password
                            </label>
                            <input
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                required
                                autoComplete="new-password"
                                className="w-full rounded border bg-background px-2 py-1.5"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-2 rounded bg-primary px-4 py-2 text-primary-foreground hover:opacity-90 disabled:opacity-50"
                        >
                            Create account
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}
