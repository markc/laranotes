import { markdown, markdownLanguage } from '@codemirror/lang-markdown';
import { languages } from '@codemirror/language-data';
import type { Extension } from '@codemirror/state';
import CodeMirror from '@uiw/react-codemirror';
import { useMemo } from 'react';
import { useAppearance } from '@/hooks/use-appearance';

type Props = {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    readOnly?: boolean;
    collabExtensions?: Extension[];
};

export function MarkdownEditor({
    value,
    onChange,
    placeholder,
    readOnly = false,
    collabExtensions,
}: Props) {
    const { resolvedAppearance } = useAppearance();
    const isDark = resolvedAppearance === 'dark';
    const isCollab = !!collabExtensions;

    const extensions = useMemo((): Extension[] => {
        const base: Extension[] = [
            markdown({ base: markdownLanguage, codeLanguages: languages }),
        ];
        if (collabExtensions) {
            base.push(...collabExtensions);
        }
        return base;
    }, [collabExtensions]);

    return (
        <CodeMirror
            value={isCollab ? undefined : value}
            onChange={isCollab ? undefined : onChange}
            placeholder={placeholder}
            theme={isDark ? 'dark' : 'light'}
            editable={!readOnly}
            readOnly={readOnly}
            extensions={extensions}
            basicSetup={{
                lineNumbers: false,
                foldGutter: false,
                highlightActiveLine: false,
                highlightActiveLineGutter: false,
            }}
            className="h-full overflow-auto text-sm"
            height="100%"
        />
    );
}
