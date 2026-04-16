import { Head, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type SettingMeta = {
    group: string;
    type: 'string' | 'int' | 'bool' | 'enum';
    default: string | number | boolean | null;
    label: string;
    options?: string[];
};

type Props = {
    settings: Record<string, Record<string, string | number | boolean>>;
    registry: Record<string, SettingMeta>;
};

const GROUP_LABELS: Record<string, string> = {
    general: 'General',
    storage: 'Notes Storage',
    editor: 'Editor',
    search: 'Search',
    display: 'Display',
};

export default function AdminSettingsIndex({ settings, registry }: Props) {
    const initial: Record<string, string | number | boolean> = {};
    for (const [, values] of Object.entries(settings)) {
        for (const [key, val] of Object.entries(values)) {
            initial[key] = val;
        }
    }

    const [form, setForm] = useState(initial);
    const [saving, setSaving] = useState(false);

    const set = (key: string, value: string | number | boolean) => {
        setForm((prev) => ({ ...prev, [key]: value }));
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        setSaving(true);
        router.patch('/admin/settings', form, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    const groups = Object.entries(registry).reduce<
        Record<string, [string, SettingMeta][]>
    >((acc, [key, meta]) => {
        (acc[meta.group] ??= []).push([key, meta]);
        return acc;
    }, {});

    const isHidden = (key: string) => {
        if (key === 'flatfile_path' || key === 'flatfile_git_auto_commit') {
            return form.notes_storage !== 'flatfile';
        }
        return false;
    };

    return (
        <>
            <Head title="Site Settings" />
            <div className="mx-auto max-w-3xl px-6 py-8">
                <h1 className="mb-6 text-2xl font-semibold">Site Settings</h1>

                <form onSubmit={submit} className="space-y-8">
                    {Object.entries(groups).map(([group, entries]) => (
                        <section key={group}>
                            <h2 className="mb-4 border-b border-border pb-2 text-lg font-medium">
                                {GROUP_LABELS[group] ?? group}
                            </h2>
                            <div className="space-y-4">
                                {entries.map(([key, meta]) => {
                                    if (isHidden(key)) return null;
                                    return (
                                        <SettingField
                                            key={key}
                                            name={key}
                                            meta={meta}
                                            value={form[key]}
                                            onChange={(v) => set(key, v)}
                                        />
                                    );
                                })}
                            </div>
                        </section>
                    ))}

                    <div className="flex items-center gap-3 border-t border-border pt-4">
                        <button
                            type="submit"
                            disabled={saving}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            {saving ? 'Saving...' : 'Save settings'}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}

function SettingField({
    name,
    meta,
    value,
    onChange,
}: {
    name: string;
    meta: SettingMeta;
    value: string | number | boolean;
    onChange: (v: string | number | boolean) => void;
}) {
    const id = `setting-${name}`;

    if (meta.type === 'bool') {
        return (
            <label
                htmlFor={id}
                className="flex cursor-pointer items-center gap-3"
            >
                <input
                    id={id}
                    type="checkbox"
                    checked={!!value}
                    onChange={(e) => onChange(e.target.checked)}
                    className="h-4 w-4 rounded border-border"
                />
                <span className="text-sm">{meta.label}</span>
            </label>
        );
    }

    if (meta.type === 'enum' && meta.options) {
        return (
            <div className="grid gap-1.5">
                <label htmlFor={id} className="text-sm font-medium">
                    {meta.label}
                </label>
                <select
                    id={id}
                    value={String(value)}
                    onChange={(e) => onChange(e.target.value)}
                    className="rounded-md border border-border bg-background px-3 py-2 text-sm"
                >
                    {meta.options.map((opt) => (
                        <option key={opt} value={opt}>
                            {opt.replace(/_/g, ' ')}
                        </option>
                    ))}
                </select>
            </div>
        );
    }

    if (meta.type === 'int') {
        return (
            <div className="grid gap-1.5">
                <label htmlFor={id} className="text-sm font-medium">
                    {meta.label}
                </label>
                <input
                    id={id}
                    type="number"
                    min={1}
                    value={Number(value)}
                    onChange={(e) => onChange(parseInt(e.target.value) || 1)}
                    className="w-32 rounded-md border border-border bg-background px-3 py-2 text-sm"
                />
            </div>
        );
    }

    return (
        <div className="grid gap-1.5">
            <label htmlFor={id} className="text-sm font-medium">
                {meta.label}
            </label>
            <input
                id={id}
                type="text"
                value={String(value ?? '')}
                onChange={(e) => onChange(e.target.value)}
                className="rounded-md border border-border bg-background px-3 py-2 text-sm"
            />
        </div>
    );
}
