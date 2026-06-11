import { useForm } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';

type Message = {
    id: number;
    body: string;
    sender: string;
    is_mine: boolean;
    created_at: string;
};

type Props = {
    messages: Message[];
    postUrl: string;
    canSend: boolean;
};

export function MessageThread({ messages, postUrl, canSend }: Props) {
    const form = useForm({ body: '' });

    function handleSend(e: React.FormEvent) {
        e.preventDefault();
        form.post(postUrl, { onSuccess: () => form.reset('body') });
    }

    return (
        <div className="rounded-xl border p-5 space-y-4">
            <p className="text-sm font-semibold">Messages ({messages.length})</p>

            <div className="space-y-3 max-h-96 overflow-y-auto pr-1">
                {messages.length === 0 && (
                    <p className="text-sm text-muted-foreground">No messages yet.</p>
                )}
                {messages.map((msg) => (
                    <div
                        key={msg.id}
                        className={cn(
                            'flex flex-col gap-1',
                            msg.is_mine ? 'items-end' : 'items-start',
                        )}
                    >
                        <span className="text-xs text-muted-foreground">{msg.sender}</span>
                        <div
                            className={cn(
                                'max-w-sm rounded-xl px-4 py-2 text-sm',
                                msg.is_mine
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-foreground',
                            )}
                        >
                            {msg.body}
                        </div>
                        <span className="text-xs text-muted-foreground">
                            {new Date(msg.created_at).toLocaleString()}
                        </span>
                    </div>
                ))}
            </div>

            {canSend && (
                <form onSubmit={handleSend} className="flex gap-2 pt-2 border-t">
                    <div className="flex-1 space-y-1">
                        <textarea
                            rows={2}
                            value={form.data.body}
                            onChange={(e) => form.setData('body', e.target.value)}
                            placeholder="Type a message..."
                            className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring resize-none"
                        />
                        <InputError message={form.errors.body} />
                    </div>
                    <Button type="submit" size="sm" disabled={form.processing || !form.data.body.trim()}>
                        Send
                    </Button>
                </form>
            )}
        </div>
    );
}
