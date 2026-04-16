import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { CollabSession } from '@/hooks/use-yjs-collab';
import { cn } from '@/lib/utils';

type Props = {
    session: CollabSession;
};

export function CollabPresenceBar({ session }: Props) {
    const { peers, status } = session;

    // Deduplicate by userId (same user in two tabs)
    const uniquePeers = Array.from(
        peers
            .reduce((map, peer) => {
                if (!map.has(peer.userId)) map.set(peer.userId, peer);
                return map;
            }, new Map<number, (typeof peers)[0]>())
            .values(),
    );

    const statusColor =
        status === 'connected'
            ? 'bg-green-500'
            : status === 'connecting'
              ? 'bg-amber-500 animate-pulse'
              : 'bg-red-500';

    const statusLabel =
        status === 'connected'
            ? 'Connected'
            : status === 'connecting'
              ? 'Connecting…'
              : 'Disconnected';

    return (
        <TooltipProvider delayDuration={300}>
            <div className="flex items-center gap-1.5">
                <Tooltip>
                    <TooltipTrigger asChild>
                        <span className={cn('h-2 w-2 rounded-full', statusColor)} />
                    </TooltipTrigger>
                    <TooltipContent side="bottom">{statusLabel}</TooltipContent>
                </Tooltip>

                {uniquePeers.map((peer) => (
                    <Tooltip key={peer.userId}>
                        <TooltipTrigger asChild>
                            <span
                                className="flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-medium text-white"
                                style={{ backgroundColor: peer.color }}
                            >
                                {initials(peer.name)}
                            </span>
                        </TooltipTrigger>
                        <TooltipContent side="bottom">{peer.name}</TooltipContent>
                    </Tooltip>
                ))}

                {uniquePeers.length > 0 && (
                    <span className="text-xs text-muted-foreground">
                        {uniquePeers.length + 1}
                    </span>
                )}
            </div>
        </TooltipProvider>
    );
}

function initials(name: string): string {
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}
