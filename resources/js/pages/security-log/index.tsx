import { Head } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';

type LogEntry = {
    id: number;
    event: string;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
};

const EVENT_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    login: 'default',
    logout: 'outline',
    login_failed: 'destructive',
    two_factor_success: 'default',
    two_factor_failed: 'destructive',
};

const EVENT_LABEL: Record<string, string> = {
    login: 'Login',
    logout: 'Logout',
    login_failed: 'Failed Login',
    two_factor_success: '2FA Success',
    two_factor_failed: '2FA Failed',
};

export default function SecurityLogIndex({ logs }: { logs: LogEntry[] }) {
    return (
        <>
            <Head title="Security Log" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <Heading title="Security Log" description="Your recent authentication activity." />

                {logs.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed p-12 text-center">
                        <ShieldCheck className="h-10 w-10 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">No security events recorded yet.</p>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">Event</th>
                                    <th className="px-4 py-3 text-left font-medium">IP Address</th>
                                    <th className="px-4 py-3 text-left font-medium">Device / Browser</th>
                                    <th className="px-4 py-3 text-left font-medium">When</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {logs.map((log) => (
                                    <tr key={log.id} className="hover:bg-muted/30">
                                        <td className="px-4 py-3">
                                            <Badge variant={EVENT_VARIANT[log.event] ?? 'outline'}>
                                                {EVENT_LABEL[log.event] ?? log.event}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs">{log.ip_address ?? '—'}</td>
                                        <td className="px-4 py-3 max-w-xs truncate text-muted-foreground text-xs">
                                            {log.user_agent ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {new Date(log.created_at).toLocaleString()}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

SecurityLogIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Security Log', href: '/my/security' },
    ],
};
