import { markdown, markdownLanguage } from '@codemirror/lang-markdown';
import { languages } from '@codemirror/language-data';
import CodeMirror from '@uiw/react-codemirror';
import { useAppearance } from '@/hooks/use-appearance';

type Props = {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
};

export function MarkdownEditor({ value, onChange, placeholder }: Props) {
    const { resolvedAppearance } = useAppearance();
    const isDark = resolvedAppearance === 'dark';

    return (
        <CodeMirror
            value={value}
            onChange={onChange}
            placeholder={placeholder}
            theme={isDark ? 'dark' : 'light'}
            extensions={[markdown({ base: markdownLanguage, codeLanguages: languages })]}
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
