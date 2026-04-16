import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { Table } from '@tiptap/extension-table';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TableRow from '@tiptap/extension-table-row';
import TaskItem from '@tiptap/extension-task-item';
import TaskList from '@tiptap/extension-task-list';
import { EditorContent, useEditor } from '@tiptap/react';
import type { Editor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { useEffect, useRef } from 'react';
import { Markdown } from 'tiptap-markdown';
import './tiptap-editor.css';

function getMarkdown(editor: Editor): string {
     
    return (editor.storage as any).markdown.getMarkdown();
}

type Props = {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    readOnly?: boolean;
};

export function TiptapEditor({
    value,
    onChange,
    placeholder = '',
    readOnly = false,
}: Props) {
    const skipNextUpdate = useRef(false);

    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                codeBlock: false,
            }),
            Markdown.configure({
                html: false,
                transformCopiedText: true,
                transformPastedText: true,
            }),
            Link.configure({
                openOnClick: false,
                HTMLAttributes: {
                    rel: 'noreferrer noopener',
                    target: '_blank',
                },
            }),
            Placeholder.configure({ placeholder }),
            TaskList,
            TaskItem.configure({ nested: true }),
            Table.configure({ resizable: false }),
            TableRow,
            TableCell,
            TableHeader,
        ],
        content: value,
        editable: !readOnly,
        onUpdate: ({ editor: e }) => {
            if (skipNextUpdate.current) {
                skipNextUpdate.current = false;

                return;
            }

            onChange(getMarkdown(e));
        },
    });

    useEffect(() => {
        if (!editor) {
return;
}

        const current = getMarkdown(editor);

        if (current !== value) {
            skipNextUpdate.current = true;
            editor.commands.setContent(value);
        }
    }, [editor, value]);

    useEffect(() => {
        editor?.setEditable(!readOnly);
    }, [editor, readOnly]);

    if (!editor) {
return null;
}

    return (
        <div className="tiptap-editor flex h-full flex-col">
            {!readOnly && <Toolbar editor={editor} />}
            <div className="flex-1 overflow-auto">
                <EditorContent editor={editor} className="h-full" />
            </div>
        </div>
    );
}

function Toolbar({ editor }: { editor: ReturnType<typeof useEditor> }) {
    if (!editor) {
return null;
}

    const btn = (
        label: string,
        action: () => void,
        active?: boolean,
        disabled?: boolean,
    ) => (
        <button
            type="button"
            onClick={action}
            className={active ? 'is-active' : ''}
            disabled={disabled}
            title={label}
        >
            {label}
        </button>
    );

    const sep = () => <span className="separator" />;

    return (
        <div className="tiptap-toolbar">
            {btn(
                'B',
                () => editor.chain().focus().toggleBold().run(),
                editor.isActive('bold'),
            )}
            {btn(
                'I',
                () => editor.chain().focus().toggleItalic().run(),
                editor.isActive('italic'),
            )}
            {btn(
                'S',
                () => editor.chain().focus().toggleStrike().run(),
                editor.isActive('strike'),
            )}
            {btn(
                'Code',
                () => editor.chain().focus().toggleCode().run(),
                editor.isActive('code'),
            )}
            {sep()}
            {btn(
                'H1',
                () => editor.chain().focus().toggleHeading({ level: 1 }).run(),
                editor.isActive('heading', { level: 1 }),
            )}
            {btn(
                'H2',
                () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
                editor.isActive('heading', { level: 2 }),
            )}
            {btn(
                'H3',
                () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
                editor.isActive('heading', { level: 3 }),
            )}
            {sep()}
            {btn(
                '•',
                () => editor.chain().focus().toggleBulletList().run(),
                editor.isActive('bulletList'),
            )}
            {btn(
                '1.',
                () => editor.chain().focus().toggleOrderedList().run(),
                editor.isActive('orderedList'),
            )}
            {btn(
                '☐',
                () => editor.chain().focus().toggleTaskList().run(),
                editor.isActive('taskList'),
            )}
            {sep()}
            {btn(
                '❝',
                () => editor.chain().focus().toggleBlockquote().run(),
                editor.isActive('blockquote'),
            )}
            {btn(
                '```',
                () => editor.chain().focus().toggleCodeBlock().run(),
                editor.isActive('codeBlock'),
            )}
            {btn('—', () => editor.chain().focus().setHorizontalRule().run())}
            {sep()}
            {btn(
                '🔗',
                () => {
                    if (editor.isActive('link')) {
                        editor.chain().focus().unsetLink().run();

                        return;
                    }

                    const url = window.prompt('URL');

                    if (url) {
                        editor.chain().focus().setLink({ href: url }).run();
                    }
                },
                editor.isActive('link'),
            )}
        </div>
    );
}
