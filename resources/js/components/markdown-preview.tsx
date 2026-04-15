import rehypeHighlight from 'rehype-highlight';
import Markdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import 'highlight.js/styles/github-dark.css';

export function MarkdownPreview({ content }: { content: string }) {
    return (
        <div className="prose prose-sm max-w-none dark:prose-invert prose-headings:mt-6 prose-headings:mb-3 prose-p:leading-relaxed prose-pre:bg-muted prose-pre:text-foreground">
            <Markdown remarkPlugins={[remarkGfm]} rehypePlugins={[rehypeHighlight]}>
                {content || '*(empty)*'}
            </Markdown>
        </div>
    );
}
