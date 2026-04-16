import Markdown from 'react-markdown';
import type { Components } from 'react-markdown';
import rehypeHighlight from 'rehype-highlight';
import remarkGfm from 'remark-gfm';
import 'highlight.js/styles/github-dark.css';

const components: Components = {
    h1: ({ children, ...props }) => (
        <h1
            className="mt-6 mb-3 border-b border-border pb-2 text-2xl font-semibold tracking-tight first:mt-0"
            {...props}
        >
            {children}
        </h1>
    ),
    h2: ({ children, ...props }) => (
        <h2
            className="mt-6 mb-3 border-b border-border pb-1 text-xl font-semibold tracking-tight first:mt-0"
            {...props}
        >
            {children}
        </h2>
    ),
    h3: ({ children, ...props }) => (
        <h3
            className="mt-5 mb-2 text-lg font-semibold tracking-tight first:mt-0"
            {...props}
        >
            {children}
        </h3>
    ),
    h4: ({ children, ...props }) => (
        <h4
            className="mt-4 mb-2 text-base font-semibold tracking-tight first:mt-0"
            {...props}
        >
            {children}
        </h4>
    ),
    h5: ({ children, ...props }) => (
        <h5
            className="mt-4 mb-2 text-sm font-semibold tracking-tight first:mt-0"
            {...props}
        >
            {children}
        </h5>
    ),
    h6: ({ children, ...props }) => (
        <h6
            className="mt-4 mb-2 text-sm font-semibold tracking-tight text-muted-foreground first:mt-0"
            {...props}
        >
            {children}
        </h6>
    ),
    p: ({ children, ...props }) => (
        <p className="my-3 leading-7 first:mt-0 last:mb-0" {...props}>
            {children}
        </p>
    ),
    a: ({ children, ...props }) => (
        <a
            className="text-primary underline underline-offset-2 hover:opacity-80"
            target="_blank"
            rel="noreferrer noopener"
            {...props}
        >
            {children}
        </a>
    ),
    ul: ({ children, ...props }) => (
        <ul
            className="my-3 ml-6 list-disc space-y-1 [&_ol]:my-1 [&_ul]:my-1"
            {...props}
        >
            {children}
        </ul>
    ),
    ol: ({ children, ...props }) => (
        <ol
            className="my-3 ml-6 list-decimal space-y-1 [&_ol]:my-1 [&_ul]:my-1"
            {...props}
        >
            {children}
        </ol>
    ),
    li: ({ children, ...props }) => (
        <li className="leading-7 marker:text-muted-foreground" {...props}>
            {children}
        </li>
    ),
    blockquote: ({ children, ...props }) => (
        <blockquote
            className="my-4 border-l-2 border-border pl-4 text-muted-foreground italic"
            {...props}
        >
            {children}
        </blockquote>
    ),
    hr: (props) => <hr className="my-6 border-border" {...props} />,
    strong: ({ children, ...props }) => (
        <strong className="font-semibold text-foreground" {...props}>
            {children}
        </strong>
    ),
    em: ({ children, ...props }) => (
        <em className="italic" {...props}>
            {children}
        </em>
    ),
    code: ({ className, children, ...props }) => {
        const isBlock = className?.includes('language-');

        if (isBlock) {
            return (
                <code className={className} {...props}>
                    {children}
                </code>
            );
        }

        return (
            <code
                className="rounded bg-muted px-1.5 py-0.5 font-mono text-[0.875em] text-foreground"
                {...props}
            >
                {children}
            </code>
        );
    },
    pre: ({ children, ...props }) => (
        <pre
            className="my-4 overflow-x-auto rounded-md border border-border bg-muted p-4 font-mono text-sm leading-relaxed [&>code]:bg-transparent [&>code]:p-0"
            {...props}
        >
            {children}
        </pre>
    ),
    table: ({ children, ...props }) => (
        <div className="my-4 overflow-x-auto">
            <table className="w-full border-collapse text-sm" {...props}>
                {children}
            </table>
        </div>
    ),
    thead: ({ children, ...props }) => (
        <thead className="border-b border-border" {...props}>
            {children}
        </thead>
    ),
    th: ({ children, ...props }) => (
        <th
            className="border border-border px-3 py-2 text-left font-semibold"
            {...props}
        >
            {children}
        </th>
    ),
    td: ({ children, ...props }) => (
        <td className="border border-border px-3 py-2" {...props}>
            {children}
        </td>
    ),
    img: ({ alt, ...props }) => (
        <img
            className="my-4 max-w-full rounded-md border border-border"
            alt={alt}
            {...props}
        />
    ),
};

export function MarkdownPreview({ content }: { content: string }) {
    return (
        <div className="max-w-none text-sm text-foreground">
            <Markdown
                remarkPlugins={[remarkGfm]}
                rehypePlugins={[rehypeHighlight]}
                components={components}
            >
                {content || '*(empty)*'}
            </Markdown>
        </div>
    );
}
