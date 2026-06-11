import { Head, Link } from '@inertiajs/react';
import { index as auditIndex, request as auditRequest } from '@/actions/App/Http/Controllers/AmlakasVerifier/SystemAuditController';
import { index as verificationIndex } from '@/actions/App/Http/Controllers/AmlakasVerifier/VerificationController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';

type LogEntry = {
    id: number;
    event: string;
    user: string | null;
    user_role: string | null;
    auditable_type: string | null;
    auditable_id: number | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
};

const EVENT_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    login: 'default',
    logout: 'outline',
    login_failed: 'destructive',
    request_submitted: 'secondary',
    request_verified: 'default',
    request_rejected: 'destructive',
    request_routed: 'default',
    response_released: 'default',
};

export default function AuditIndex({ logs }: { logs: LogEntry[] }) {
    return (
        <>
            <Head title="System Audit Log" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <Heading title="System Audit Log" description="All auditable events across the platform." />

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">Event</th>
                                <th className="px-4 py-3 text-left font-medium">User</th>
                                <th className="px-4 py-3 text-left font-medium">Role</th>
                                <th className="px-4 py-3 text-left font-medium">Subject</th>
                                <th className="px-4 py-3 text-left font-medium">Changes</th>
                                <th className="px-4 py-3 text-left font-medium">IP</th>
                                <th className="px-4 py-3 text-left font-medium">When</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {logs.map((log) => (
                                <tr key={log.id} className="hover:bg-muted/30">
                                    <td className="px-4 py-3">
                                        <Badge variant={EVENT_VARIANT[log.event] ?? 'outline'}>
                                            {log.event.replace(/_/g, ' ')}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">{log.user ?? '—'}</td>
                                    <td className="px-4 py-3 text-xs text-muted-foreground">
                                        {log.user_role?.replace(/_/g, ' ') ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-xs">
                                        {log.auditable_type && log.auditable_id ? (
                                            log.auditable_type === 'request' ? (
                                                <Link
                                                    href={auditRequest.url(log.auditable_id)}
                                                    className="text-primary hover:underline font-mono"
                                                >
                                                    request #{log.auditable_id}
                                                </Link>
                                            ) : (
                                                <span className="font-mono">{log.auditable_type} #{log.auditable_id}</span>
                                            )
                                        ) : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-xs text-muted-foreground max-w-xs truncate">
                                        {log.new_values
                                            ? Object.entries(log.new_values).map(([k, v]) => `${k}: ${v}`).join(', ')
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3 font-mono text-xs">{log.ip_address ?? '—'}</td>
                                    <td className="px-4 py-3 text-muted-foreground whitespace-nowrap">
                                        {new Date(log.created_at).toLocaleString()}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {logs.length === 0 && (
                        <p className="p-8 text-center text-sm text-muted-foreground">No audit events yet.</p>
                    )}
                </div>
            </div>
        </>
    );
}

AuditIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'System Audit', href: auditIndex.url() },
    ],
};
