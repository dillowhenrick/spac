import { Head, Link } from '@inertiajs/react';
import { AlertCircle, CheckCircle, Clock, FileText, Inbox, Shield } from 'lucide-react';
import { create, show as requestShow } from '@/actions/App/Http/Controllers/Requester/RequestController';
import { index as verificationIndex, show as verificationShow } from '@/actions/App/Http/Controllers/AmlakasVerifier/VerificationController';
import { queue as institutionQueue, show as managerShow } from '@/actions/App/Http/Controllers/Institution/ManagerController';
import { index as assignedIndex, show as staffShow } from '@/actions/App/Http/Controllers/Institution/StaffController';
import RequestTypeBadge, { type RequestTypeData } from '@/components/request-type-badge';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';

type RecentRow = {
    id: number;
    reference_number: string;
    request_type: RequestTypeData;
    status: string;
    submitted_at?: string | null;
    assigned_at?: string | null;
    request_due_at?: string | null;
    target_institution?: string;
    agency?: string;
    requester?: string;
};

type RequesterStats = { total: number; submitted: number; in_progress: number; released: number; recent: RecentRow[] };
type VerifierStats = { pending: number; verified_this_month: number; overdue_returns: number; recent: RecentRow[] };
type ManagerStats = { queue: number; pending_review: number; overdue_returns: number; recent: RecentRow[] };
type StaffStats = { assigned: number; in_progress: number; recent: RecentRow[] };
type Stats = { requester?: RequesterStats; verifier?: VerifierStats; manager?: ManagerStats; staff?: StaffStats };

const STATUS_LABELS: Record<string, string> = {
    draft: 'Draft', submitted: 'Submitted', under_verification: 'Under Verification',
    verification_rejected: 'Rejected', verified: 'Verified', routed: 'Routed',
    assigned: 'Assigned', in_progress: 'In Progress', pending_manager_review: 'Pending Review',
    revision_requested: 'Revision Requested', approved: 'Approved', released: 'Released', closed: 'Closed',
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    draft: 'outline', submitted: 'secondary', under_verification: 'secondary',
    verification_rejected: 'destructive', verified: 'default', routed: 'default',
    assigned: 'default', in_progress: 'default', pending_manager_review: 'secondary',
    revision_requested: 'outline', approved: 'default', released: 'default', closed: 'outline',
};

function StatCard({ label, value, href, icon: Icon, danger = false }: {
    label: string; value: number; href?: string; icon: React.ElementType; danger?: boolean;
}) {
    const content = (
        <div className={`flex items-center justify-between rounded-xl border p-5 transition-colors ${danger && value > 0 ? 'border-destructive/40 bg-destructive/5' : 'hover:bg-muted/30'}`}>
            <div>
                <p className={`text-2xl font-bold ${danger && value > 0 ? 'text-destructive' : ''}`}>{value}</p>
                <p className="mt-0.5 text-sm text-muted-foreground">{label}</p>
            </div>
            <Icon className={`h-8 w-8 ${danger && value > 0 ? 'text-destructive/60' : 'text-muted-foreground/50'}`} />
        </div>
    );
    return href ? <Link href={href}>{content}</Link> : content;
}

function SectionHeader({ title, viewAllHref }: { title: string; viewAllHref: string }) {
    return (
        <div className="flex items-center justify-between">
            <h2 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{title}</h2>
            <Link href={viewAllHref} className="text-xs font-medium text-primary hover:underline">View all →</Link>
        </div>
    );
}

function RecentTable({ rows, columns }: {
    rows: RecentRow[];
    columns: { label: string; render: (row: RecentRow) => React.ReactNode }[];
}) {
    if (rows.length === 0) {
        return <p className="rounded-xl border border-dashed py-8 text-center text-sm text-muted-foreground">Nothing to show.</p>;
    }

    return (
        <div className="overflow-hidden rounded-xl border">
            <table className="w-full text-sm">
                <thead className="border-b bg-muted/50">
                    <tr>
                        {columns.map((c) => (
                            <th key={c.label} className="px-4 py-2.5 text-left text-xs font-medium">{c.label}</th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {rows.map((row) => (
                        <tr key={row.id} className="transition-colors hover:bg-muted/30">
                            {columns.map((c) => (
                                <td key={c.label} className="px-4 py-2.5">{c.render(row)}</td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function RequesterSection({ stats }: { stats: RequesterStats }) {
    return (
        <div className="space-y-4">
            <SectionHeader title="My Requests" viewAllHref="/requests" />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Total Requests" value={stats.total} href="/requests" icon={FileText} />
                <StatCard label="Submitted" value={stats.submitted} href="/requests" icon={Clock} />
                <StatCard label="In Progress" value={stats.in_progress} href="/requests" icon={Inbox} />
                <StatCard label="Released" value={stats.released} href="/requests" icon={CheckCircle} />
            </div>
            {stats.total === 0 ? (
                <div className="rounded-xl border border-dashed p-6 text-center">
                    <p className="text-sm text-muted-foreground">No requests yet.</p>
                    <Link href={create.url()} className="mt-2 inline-block text-sm font-medium text-primary hover:underline">Create your first request</Link>
                </div>
            ) : (
                <RecentTable rows={stats.recent} columns={[
                    { label: 'Reference', render: (r) => <Link href={requestShow.url(r.id)} className="font-mono font-medium text-primary hover:underline">{r.reference_number}</Link> },
                    { label: 'Type', render: (r) => <RequestTypeBadge type={r.request_type} /> },
                    { label: 'Institution', render: (r) => <span className="text-muted-foreground">{r.target_institution}</span> },
                    { label: 'Status', render: (r) => <Badge variant={STATUS_VARIANT[r.status] ?? 'outline'}>{STATUS_LABELS[r.status] ?? r.status}</Badge> },
                    { label: 'Submitted', render: (r) => <span className="text-muted-foreground">{r.submitted_at ? new Date(r.submitted_at).toLocaleDateString() : '—'}</span> },
                ]} />
            )}
        </div>
    );
}

function VerifierSection({ stats }: { stats: VerifierStats }) {
    return (
        <div className="space-y-4">
            <SectionHeader title="Verification Queue" viewAllHref={verificationIndex.url()} />
            <div className="grid gap-4 sm:grid-cols-3">
                <StatCard label="Pending Verification" value={stats.pending} href={verificationIndex.url()} icon={Inbox} />
                <StatCard label="Verified This Month" value={stats.verified_this_month} icon={CheckCircle} />
                <StatCard label="Overdue SLA Returns" value={stats.overdue_returns} icon={AlertCircle} danger />
            </div>
            <RecentTable rows={stats.recent} columns={[
                { label: 'Reference', render: (r) => <Link href={verificationShow.url(r.id)} className="font-mono font-medium text-primary hover:underline">{r.reference_number}</Link> },
                { label: 'Type', render: (r) => <RequestTypeBadge type={r.request_type} /> },
                { label: 'Agency', render: (r) => <span className="text-muted-foreground">{r.agency}</span> },
                { label: 'Institution', render: (r) => <span className="text-muted-foreground">{r.target_institution}</span> },
                { label: 'Status', render: (r) => <Badge variant={STATUS_VARIANT[r.status] ?? 'outline'}>{STATUS_LABELS[r.status] ?? r.status}</Badge> },
                { label: 'Submitted', render: (r) => <span className="text-muted-foreground">{r.submitted_at ? new Date(r.submitted_at).toLocaleDateString() : '—'}</span> },
            ]} />
        </div>
    );
}

function ManagerSection({ stats }: { stats: ManagerStats }) {
    return (
        <div className="space-y-4">
            <SectionHeader title="Institution Queue" viewAllHref={institutionQueue.url()} />
            <div className="grid gap-4 sm:grid-cols-3">
                <StatCard label="Active Queue" value={stats.queue} href={institutionQueue.url()} icon={Inbox} />
                <StatCard label="Pending Review" value={stats.pending_review} href={institutionQueue.url()} icon={Shield} />
                <StatCard label="Overdue SLA Returns" value={stats.overdue_returns} icon={AlertCircle} danger />
            </div>
            <RecentTable rows={stats.recent} columns={[
                { label: 'Reference', render: (r) => <Link href={managerShow.url(r.id)} className="font-mono font-medium text-primary hover:underline">{r.reference_number}</Link> },
                { label: 'Type', render: (r) => <RequestTypeBadge type={r.request_type} /> },
                { label: 'Agency', render: (r) => <span className="text-muted-foreground">{r.agency}</span> },
                { label: 'Status', render: (r) => <Badge variant={STATUS_VARIANT[r.status] ?? 'outline'}>{STATUS_LABELS[r.status] ?? r.status}</Badge> },
                {
                    label: 'Return Due', render: (r) => {
                        if (!r.request_due_at) return <span className="text-muted-foreground">—</span>;
                        const overdue = new Date(r.request_due_at) < new Date();
                        return <span className={overdue ? 'flex items-center gap-1 font-medium text-destructive' : 'text-muted-foreground'}>
                            {overdue && <AlertCircle className="h-3 w-3" />}
                            {new Date(r.request_due_at).toLocaleDateString()}
                        </span>;
                    }
                },
            ]} />
        </div>
    );
}

function StaffSection({ stats }: { stats: StaffStats }) {
    return (
        <div className="space-y-4">
            <SectionHeader title="My Assignments" viewAllHref={assignedIndex.url()} />
            <div className="grid gap-4 sm:grid-cols-2">
                <StatCard label="Assigned Requests" value={stats.assigned} href={assignedIndex.url()} icon={FileText} />
                <StatCard label="In Progress" value={stats.in_progress} href={assignedIndex.url()} icon={Clock} />
            </div>
            <RecentTable rows={stats.recent} columns={[
                { label: 'Reference', render: (r) => <Link href={staffShow.url(r.id)} className="font-mono font-medium text-primary hover:underline">{r.reference_number}</Link> },
                { label: 'Type', render: (r) => <RequestTypeBadge type={r.request_type} /> },
                { label: 'Agency', render: (r) => <span className="text-muted-foreground">{r.agency}</span> },
                { label: 'Status', render: (r) => <Badge variant={STATUS_VARIANT[r.status] ?? 'outline'}>{STATUS_LABELS[r.status] ?? r.status}</Badge> },
                { label: 'Assigned', render: (r) => <span className="text-muted-foreground">{r.assigned_at ? new Date(r.assigned_at).toLocaleDateString() : '—'}</span> },
            ]} />
        </div>
    );
}

export default function Dashboard({ stats }: { stats: Stats }) {
    const hasAnyStats = stats.requester || stats.verifier || stats.manager || stats.staff;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-10 p-6">
                {!hasAnyStats && (
                    <div className="flex flex-1 items-center justify-center">
                        <p className="text-sm text-muted-foreground">No dashboard data available for your role.</p>
                    </div>
                )}
                {stats.requester && <RequesterSection stats={stats.requester} />}
                {stats.verifier && <VerifierSection stats={stats.verifier} />}
                {stats.manager && <ManagerSection stats={stats.manager} />}
                {stats.staff && <StaffSection stats={stats.staff} />}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
