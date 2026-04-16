import { useMemo } from 'react';
import type { Extension } from '@codemirror/state';
import { yCollab } from 'y-codemirror.next';
import type * as Y from 'yjs';

type Options = {
    ytext: Y.Text;
    provider: { awareness: any };
    undoManager: Y.UndoManager;
};

export function useCollabExtensions(
    options: Options | null,
): Extension[] | undefined {
    return useMemo(() => {
        if (!options) return undefined;
        return [
            yCollab(options.ytext, options.provider.awareness, {
                undoManager: options.undoManager,
            }),
        ];
    }, [options?.ytext, options?.provider?.awareness, options?.undoManager]);
}
