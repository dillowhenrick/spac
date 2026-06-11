import { Head, Link, router } from '@inertiajs/react';
import { FilePlus, FileText } from 'lucide-react';
import { index, create, show } from '@/actions/App/Http/Controllers/Requester/RequestController';
import Heading from '@/components/heading';
import RequestTypeBadge, { type RequestTypeData } from '@/components/request-type-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type RequestRow = {
    id: number;
    reference_number: string;
    request_type: RequestTypeData;
    status: string;
    target_institution: string;
    submitted_at: string | null;
    created_at: string;
};

type Filters = { status?: string; date_from?: string; date_to?: string };

const STATUS_LABELS: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Submitted',
    under_verification: 'Under Verification',
    verification_rejected: 'Rejected',
    verified: 'Verified',
    routed: 'Routed',
    assigned: 'Assigned',
    in_progress: 'In Progress',
    pending_manager_review: 'Pending Review',
    revision_requested: 'Revision Requested',
    approved: 'Approved',
    released: 'Released',
    closed: 'Closed',
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    draft: 'outline',
    submitted: 'secondary',
    under_verification: 'secondary',
    verification_rejected: 'destructive',
    verified: 'default',
    routed: 'default',
    assigned: 'default',
    in_progress: 'default',
    pending_manager_review: 'secondary',
    revision_requested: 'outline',
    approved: 'default',
    released: 'default',
    closed: 'outline',
};

const STATUSES = Object.entries(STATUS_LABELS).map(([value, label]) => ({ value, label }));

function RequestFilters({ filters }: { filters: Filters }) {
    function apply(patch: Partial<Filters>) {
        const next = { ...filters, ...patch };
        Object.keys(next).forEach((k) => { if (!next[k as keyof Filters]) delete next[k as keyof Filters]; });
        router.get(index.url(), next as Record<string, string>, { preserveState: true, replace: true });
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
                <Button variant="ghost" size="sm" className="h-8 self-end" onClick={() => router.get(index.url(), {}, { replace: true })}>Clear</Button>
            )}
        </div>
    );
}

export default function RequestsIndex({ requests, filters }: { requests: RequestRow[]; filters: Filters }) {
    return (
        <>
            <Head title="Requests" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <Heading title="Requests" description="Manage and track your submitted requests." />
                    <Button asChild>
                        <Link href={create.url()}>
                            <FilePlus className="mr-2 h-4 w-4" />
                            New Request
                        </Link>
                    </Button>
                </div>

                <RequestFilters filters={filters} />

                {requests.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed p-12 text-center">
                        <FileText className="h-10 w-10 text-muted-foreground" />
                        <p className="text-sm font-medium">No requests yet</p>
                        <p className="text-sm text-muted-foreground">
                            Submit a new request to get started.
                        </p>
                        <Button asChild size="sm">
                            <Link href={create.url()}>New Request</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">Reference</th>
                                    <th className="px-4 py-3 text-left font-medium">Subject</th>
                                    <th className="px-4 py-3 text-left font-medium">Type</th>
                                    <th className="px-4 py-3 text-left font-medium">Institution</th>
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
                                        <td className="px-4 py-3">{req.target_institution}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant={STATUS_VARIANT[req.status] ?? 'outline'}>
                                                {STATUS_LABELS[req.status] ?? req.status}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {req.submitted_at
                                                ? new Date(req.submitted_at).toLocaleDateString()
                                                : '—'}
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

RequestsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Requests', href: index.url() },
    ],
};
