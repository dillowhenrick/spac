import { Head, Link, router } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';
import { index, show } from '@/actions/App/Http/Controllers/Institution/StaffController';
import Heading from '@/components/heading';
import RequestTypeBadge, { type RequestTypeData } from '@/components/request-type-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type AssignmentRow = {
    id: number;
    request: {
        id: number;
        reference_number: string;
        request_type: RequestTypeData;
        status: string;
        agency: string;
        target_institution: string;
        response_status: string | null;
    };
    assigned_at: string;
};

type Filters = { status?: string; date_from?: string; date_to?: string };

const STATUSES = [
    { value: 'assigned', label: 'Assigned' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'pending_manager_review', label: 'Pending Review' },
    { value: 'revision_requested', label: 'Revision Requested' },
];

function AssignmentFilters({ filters }: { filters: Filters }) {
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
                <span className="text-xs font-medium text-muted-foreground">Assigned From</span>
                <Input type="date" className="h-8 w-36 bg-background text-sm" value={filters.date_from ?? ''} onChange={(e) => apply({ date_from: e.target.value || undefined })} />
            </div>
            <div className="flex flex-col gap-1">
                <span className="text-xs font-medium text-muted-foreground">Assigned To</span>
                <Input type="date" className="h-8 w-36 bg-background text-sm" value={filters.date_to ?? ''} onChange={(e) => apply({ date_to: e.target.value || undefined })} />
            </div>
            {(filters.status || filters.date_from || filters.date_to) && (
                <Button variant="ghost" size="sm" className="h-8 self-end" onClick={() => router.get(index.url(), {}, { replace: true })}>Clear</Button>
            )}
        </div>
    );
}

export default function InstitutionAssigned({ assignments, filters }: { assignments: AssignmentRow[]; filters: Filters }) {
    return (
        <>
            <Head title="My Assignments" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <Heading title="My Assignments" description="Requests assigned to you for processing." />

                <AssignmentFilters filters={filters} />

                {assignments.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed p-12 text-center">
                        <ClipboardCheck className="h-10 w-10 text-muted-foreground" />
                        <p className="text-sm font-medium">No assignments</p>
                        <p className="text-sm text-muted-foreground">No requests are currently assigned to you.</p>
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
                                    <th className="px-4 py-3 text-left font-medium">Status</th>
                                    <th className="px-4 py-3 text-left font-medium">Response</th>
                                    <th className="px-4 py-3 text-left font-medium">Assigned</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {assignments.map((a) => (
                                    <tr key={a.id} className="hover:bg-muted/30 transition-colors">
                                        <td className="px-4 py-3">
                                            <Link
                                                href={show.url(a.request.id)}
                                                className="font-mono font-medium text-primary hover:underline"
                                            >
                                                {a.request.reference_number}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3 max-w-xs truncate">{a.request.reference_number}</td>
                                        <td className="px-4 py-3"><RequestTypeBadge type={a.request.request_type} /></td>
                                        <td className="px-4 py-3">{a.request.agency}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant="outline">{a.request.status.replace(/_/g, ' ')}</Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            {a.request.response_status ? (
                                                <Badge variant={a.request.response_status === 'pending_approval' ? 'default' : 'secondary'}>
                                                    {a.request.response_status.replace(/_/g, ' ')}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {new Date(a.assigned_at).toLocaleDateString()}
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

InstitutionAssigned.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'My Assignments', href: index.url() },
    ],
};
