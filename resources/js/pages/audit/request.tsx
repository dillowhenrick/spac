import { Head } from '@inertiajs/react';
import { index as auditIndex } from '@/actions/App/Http/Controllers/AmlakasVerifier/SystemAuditController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';

type LogEntry = {
    id: number;
    event: string;
    user: string | null;
    user_role: string | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
};

type RequestSummary = { id: number; reference_number: string };

export default function AuditRequest({ request, logs }: { request: RequestSummary; logs: LogEntry[] }) {
    return (
        <>
            <Head title={`Audit — ${request.reference_number}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <Heading
                    title={`Audit Trail — ${request.reference_number}`}
                    description={request.reference_number}
                />

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">Event</th>
                                <th className="px-4 py-3 text-left font-medium">By</th>
                                <th className="px-4 py-3 text-left font-medium">Role</th>
                                <th className="px-4 py-3 text-left font-medium">Previous</th>
                                <th className="px-4 py-3 text-left font-medium">New</th>
                                <th className="px-4 py-3 text-left font-medium">IP</th>
                                <th className="px-4 py-3 text-left font-medium">When</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {logs.map((log) => (
                                <tr key={log.id} className="hover:bg-muted/30">
                                    <td className="px-4 py-3">
                                        <Badge variant="outline">{log.event.replace(/_/g, ' ')}</Badge>
                                    </td>
                                    <td className="px-4 py-3">{log.user ?? 'System'}</td>
                                    <td className="px-4 py-3 text-xs text-muted-foreground">
                                        {log.user_role?.replace(/_/g, ' ') ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-xs text-muted-foreground">
                                        {log.old_values
                                            ? Object.entries(log.old_values).map(([k, v]) => `${k}: ${v}`).join(', ')
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-xs text-muted-foreground">
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
                        <p className="p-8 text-center text-sm text-muted-foreground">No audit events for this request yet.</p>
                    )}
                </div>
            </div>
        </>
    );
}

AuditRequest.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'System Audit', href: auditIndex.url() },
        { title: 'Request Audit', href: '#' },
    ],
};
