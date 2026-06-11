import { Head, Link, router } from '@inertiajs/react';
import { AlertCircle, InboxIcon } from 'lucide-react';
import { queue, show } from '@/actions/App/Http/Controllers/Institution/ManagerController';
import Heading from '@/components/heading';
import RequestTypeBadge, { type RequestTypeData } from '@/components/request-type-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

type QueueRow = {
    id: number;
    reference_number: string;
    request_type: RequestTypeData;
    status: string;
    agency: string;
    requester: string;
    assigned_to: string | null;
    submitted_at: string | null;
    request_due_at: string | null;
};

type Filters = { status?: string; date_from?: string; date_to?: string };

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline'> = {
    routed: 'secondary',
    assigned: 'default',
    in_progress: 'default',
    pending_manager_review: 'outline',
};

const STATUSES = [
    { value: 'routed', label: 'Routed' },
    { value: 'assigned', label: 'Assigned' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'pending_manager_review', label: 'Pending Review' },
];

function QueueFilters({ filters }: { filters: Filters }) {
    function apply(patch: Partial<Filters>) {
        const next = { ...filters, ...patch };
        Object.keys(next).forEach((k) => { if (!next[k as keyof Filters]) delete next[k as keyof Filters]; });
        router.get(queue.url(), next as Record<string, string>, { preserveState: true, replace: true });
    }

    return (
        <div className="flex flex-wrap items-end gap-3 rounded-xl border bg-muted/30 p-4">
            <div className="flex flex-col gap-1">
                <span className="text-xs font-medium text-muted-foreground">Status</span>
                <Select value={filters.status ?? ''} onValueChange={(v) => apply({ status: v || undefined })}>
                    <SelectTrigger className="h-8 w-44 bg-background text-sm">
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        {STATUSES.map((s) => <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>)}
                    </SelectContent>
                </Select>
            </div>
            <div className="flex flex-col gap-1">
                <span className="text-xs font-medium text-muted-foreground">Submitted From</span>
                <Input type="date" className="h-8 w-36 bg-background text-sm" value={filters.date_from ?? ''} onChange={(e) => apply({ date_from: e.target.value || undefined })} />
            </div>
            <div className="flex flex-col gap-1">
                <span className="text-xs font-medium text-muted-foreground">Submitted To</span>
                <Input type="date" className="h-8 w-36 bg-background text-sm" value={filters.date_to ?? ''} onChange={(e) => apply({ date_to: e.target.value || undefined })} />
            </div>
            {(filters.status || filters.date_from || filters.date_to) && (
                <Button variant="ghost" size="sm" className="h-8 self-end" onClick={() => router.get(queue.url(), {}, { replace: true })}>Clear</Button>
            )}
        </div>
    );
}

function OverdueIndicator({ returnDueAt }: { returnDueAt: string }) {
    const overdue = new Date(returnDueAt) < new Date();
    if (!overdue) return null;
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <AlertCircle className="inline h-3.5 w-3.5 text-destructive" />
            </TooltipTrigger>
            <TooltipContent>SLA overdue — return due {new Date(returnDueAt).toLocaleDateString()}</TooltipContent>
        </Tooltip>
    );
}

export default function InstitutionQueue({ requests, filters }: { requests: QueueRow[]; filters: Filters }) {
    return (
        <>
            <Head title="Institution Queue" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <Heading title="Institution Queue" description="Manage and assign incoming requests for your institution." />

                <QueueFilters filters={filters} />

                {requests.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed p-12 text-center">
                        <InboxIcon className="h-10 w-10 text-muted-foreground" />
                        <p className="text-sm font-medium">Queue is empty</p>
                        <p className="text-sm text-muted-foreground">No requests routed to your institution.</p>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">Reference</th>
                                    <th className="px-4 py-3 text-left font-medium">Subject</th>
                                    <th className="px-4 py-3 text-left font-medium">Type</th>
                                    <th className="px-4 py-3 text-left font-medium">Agency</th>
                                    <th className="px-4 py-3 text-left font-medium">Assigned To</th>
                                    <th className="px-4 py-3 text-left font-medium">Status</th>
                                    <th className="px-4 py-3 text-left font-medium">Submitted</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {requests.map((req) => (
                                    <tr key={req.id} className="hover:bg-muted/30 transition-colors">
                                        <td className="px-4 py-3">
                                            <Link
                                                href={show.url(req.id)}
                                                className="font-mono font-medium text-primary hover:underline"
                                            >
                                                {req.reference_number}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3 max-w-xs truncate">{req.reference_number}</td>
                                        <td className="px-4 py-3"><RequestTypeBadge type={req.request_type} /></td>
                                        <td className="px-4 py-3">{req.agency}</td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {req.assigned_to ?? '—'}
                                            {req.request_due_at && <span className="ml-1.5"><OverdueIndicator returnDueAt={req.request_due_at} /></span>}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={STATUS_VARIANT[req.status] ?? 'outline'}>
                                                {req.status.replace(/_/g, ' ')}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {req.submitted_at ? new Date(req.submitted_at).toLocaleDateString() : '—'}
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

InstitutionQueue.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Institution Queue', href: queue.url() },
    ],
};
