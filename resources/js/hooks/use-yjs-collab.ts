import { useEffect, useMemo, useRef, useState } from 'react';
import { WebsocketProvider } from 'y-websocket';
import * as Y from 'yjs';
import { getCollabColor } from '@/lib/collab-colors';

export type CollabPeer = {
    userId: number;
    name: string;
    color: string;
    clientId: number;
};

export type CollabConfig = {
    token: string;
    ws_url: string;
    user: { id: number; name: string };
    can_edit: boolean;
};

export type CollabSession = {
    ydoc: Y.Doc;
    ytext: Y.Text;
    provider: WebsocketProvider;
    undoManager: Y.UndoManager;
    status: 'connecting' | 'connected' | 'disconnected';
    peers: CollabPeer[];
    canEdit: boolean;
    isCollabActive: boolean;
    connected: boolean;
};

export function useYjsCollab(config: CollabConfig | null): CollabSession | null {
    const ydoc = useMemo(() => new Y.Doc(), [config?.ws_url]);
    const ytext = useMemo(() => ydoc.getText('body'), [ydoc]);
    const [status, setStatus] = useState<'connecting' | 'connected' | 'disconnected'>('connecting');
    const [peers, setPeers] = useState<CollabPeer[]>([]);
    const providerRef = useRef<WebsocketProvider | null>(null);

    useEffect(() => {
        if (!config) return;

        // y-websocket constructs: serverUrl/roomname?params
        // ws_url is already "ws://host:port/ws/note/123", so serverUrl is the parent
        // and roomname is the note ID
        const lastSlash = config.ws_url.lastIndexOf('/');
        const serverUrl = config.ws_url.substring(0, lastSlash);
        const roomName = config.ws_url.substring(lastSlash + 1);

        const provider = new WebsocketProvider(serverUrl, roomName, ydoc, {
            params: { token: config.token },
        });

        provider.on('status', ({ status: s }: { status: string }) => {
            setStatus(s as 'connecting' | 'connected' | 'disconnected');
        });

        const awareness = provider.awareness;
        const userColor = getCollabColor(config.user.id);
        awareness.setLocalStateField('user', {
            userId: config.user.id,
            name: config.user.name,
            color: userColor.color,
            colorLight: userColor.light,
        });

        const updatePeers = () => {
            const otherPeers: CollabPeer[] = [];
            awareness.getStates().forEach((state: any, clientId: number) => {
                if (clientId === ydoc.clientID) return;
                if (!state.user) return;
                otherPeers.push({
                    userId: state.user.userId,
                    name: state.user.name,
                    color: state.user.color,
                    clientId,
                });
            });
            setPeers(otherPeers);
        };

        awareness.on('change', updatePeers);
        providerRef.current = provider;

        return () => {
            awareness.off('change', updatePeers);
            provider.destroy();
            providerRef.current = null;
            setStatus('disconnected');
            setPeers([]);
        };
    }, [config?.token, config?.ws_url, config?.user?.id]);

    const undoManager = useMemo(() => new Y.UndoManager(ytext), [ytext]);

    useEffect(() => {
        return () => {
            undoManager.destroy();
            ydoc.destroy();
        };
    }, [ydoc, undoManager]);

    if (!config || !providerRef.current) return null;

    return {
        ydoc,
        ytext,
        provider: providerRef.current,
        undoManager,
        status,
        peers,
        canEdit: config.can_edit,
        isCollabActive: peers.length > 0,
        connected: status === 'connected',
    };
}
