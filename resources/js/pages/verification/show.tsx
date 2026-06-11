import { Head, router, useForm } from '@inertiajs/react';
import { Paperclip } from 'lucide-react';
import {
    approve,
    index,
    reject,
    route,
    show,
} from '@/actions/App/Http/Controllers/AmlakasVerifier/VerificationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import RequestTypeBadge, { type RequestTypeData } from '@/components/request-type-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Attachment = { id: number; original_name: string; mime_type: string; size: number };

type Verification = { decision: string; notes: string | null; verified_at: string };

type RequestDetail = {
    id: number;
    reference_number: string;
    additional_context: string | null;
    request_type: RequestTypeData;
    nature_of_case: string | null;
    status: string;
    agency: string;
    requester: string;
    target_institution: string;
    submitted_at: string | null;
    legal_process_signed_at: string | null;
    warrant_expires_at: string | null;
    request_due_at: string | null;
    attachments: Attachment[];
    verification: Verification | null;
    routed: boolean;
    can: { approve: boolean; reject: boolean; route: boolean };
};

function formatBytes(bytes: number) {
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function VerificationShow({ request }: { request: RequestDetail }) {
    const approveForm = useForm({ notes: '' });
    const rejectForm = useForm({ notes: '' });

    function handleApprove(e: React.FormEvent) {
        e.preventDefault();
        approveForm.post(approve.url(request.id));
    }

    function handleReject(e: React.FormEvent) {
        e.preventDefault();
        rejectForm.post(reject.url(request.id));
    }

    function handleRoute() {
        router.post(route.url(request.id));
    }

    return (
        <>
            <Head title={request.reference_number} />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <Heading title={request.reference_number} />
                    <Badge>{request.status.replace(/_/g, ' ')}</Badge>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-4 rounded-xl border p-5">
                        <p className="text-sm font-semibold">Request Details</p>
                        <dl className="space-y-3 text-sm">
                            {[
                                ['Type', <RequestTypeBadge type={request.request_type} />],
                                ...(request.nature_of_case ? [['Nature of Case', request.nature_of_case]] : []),
                                ['Agency', request.agency],
                                ['Requester', request.requester],
                                ['Target Institution', request.target_institution],
                                ['Submitted', request.submitted_at ? new Date(request.submitted_at).toLocaleDateString() : '—'],
                            ].map(([label, value]) => (
                                <div key={String(label)} className="flex justify-between">
                                    <dt className="text-muted-foreground">{label}</dt>
                                    <dd>{value}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>

                    {(request.legal_process_signed_at || request.request_due_at) && (
                        <div className="space-y-4 rounded-xl border p-5">
                            <p className="text-sm font-semibold">Warrant / SLA</p>
                            <dl className="space-y-3 text-sm">
                                {request.legal_process_signed_at && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Signed</dt>
                                        <dd>{new Date(request.legal_process_signed_at).toLocaleDateString()}</dd>
                                    </div>
                                )}
                                {request.warrant_expires_at && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Expires</dt>
                                        <dd>{new Date(request.warrant_expires_at).toLocaleDateString()}</dd>
                                    </div>
                                )}
                                {request.request_due_at && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Return Due</dt>
                                        <dd className="font-medium text-destructive">
                                            {new Date(request.request_due_at).toLocaleDateString()}
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </div>
                    )}
                </div>

                {request.additional_context && (
                    <div className="rounded-xl border p-5">
                        <p className="mb-3 text-sm font-semibold">Additional Context</p>
                        <p className="whitespace-pre-wrap text-sm text-muted-foreground">{request.additional_context}</p>
                    </div>
                )}

                {request.attachments.length > 0 && (
                    <div className="rounded-xl border p-5">
                        <p className="mb-4 text-sm font-semibold">Attachments ({request.attachments.length})</p>
                        <ul className="divide-y">
                            {request.attachments.map((att) => (
                                <li key={att.id} className="flex items-center gap-2 py-2.5 text-sm">
                                    <Paperclip className="h-4 w-4 text-muted-foreground" />
                                    <span className="font-medium">{att.original_name}</span>
                                    <span className="text-muted-foreground">{formatBytes(att.size)}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {request.verification && (
                    <div className={`rounded-xl border p-5 ${request.verification.decision === 'approved' ? 'border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950/20' : 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/20'}`}>
                        <p className="mb-2 text-sm font-semibold capitalize">
                            {request.verification.decision} — {new Date(request.verification.verified_at).toLocaleDateString()}
                        </p>
                        {request.verification.notes && (
                            <p className="text-sm text-muted-foreground">{request.verification.notes}</p>
                        )}
                    </div>
                )}

                {(request.can.approve || request.can.reject || request.can.route) && (
                    <div className="grid gap-6 md:grid-cols-2">
                        {request.can.approve && (
                            <form onSubmit={handleApprove} className="space-y-4 rounded-xl border p-5">
                                <p className="text-sm font-semibold">Approve Request</p>
                                <div className="grid gap-2">
                                    <Label htmlFor="approve-notes">Notes (optional)</Label>
                                    <textarea
                                        id="approve-notes"
                                        rows={3}
                                        value={approveForm.data.notes}
                                        onChange={(e) => approveForm.setData('notes', e.target.value)}
                                        className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        placeholder="Optional verification notes..."
                                    />
                                    <InputError message={approveForm.errors.notes} />
                                </div>
                                <Button type="submit" disabled={approveForm.processing}>
                                    Approve
                                </Button>
                            </form>
                        )}

                        {request.can.reject && (
                            <form onSubmit={handleReject} className="space-y-4 rounded-xl border p-5">
                                <p className="text-sm font-semibold">Reject Request</p>
                                <div className="grid gap-2">
                                    <Label htmlFor="reject-notes">Rejection Reason</Label>
                                    <textarea
                                        id="reject-notes"
                                        rows={3}
                                        value={rejectForm.data.notes}
                                        onChange={(e) => rejectForm.setData('notes', e.target.value)}
                                        className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        placeholder="Reason for rejection (required)..."
                                    />
                                    <InputError message={rejectForm.errors.notes} />
                                </div>
                                <Button type="submit" variant="destructive" disabled={rejectForm.processing}>
                                    Reject
                                </Button>
                            </form>
                        )}

                        {request.can.route && (
                            <div className="space-y-4 rounded-xl border p-5">
                                <p className="text-sm font-semibold">Route to Institution</p>
                                <p className="text-sm text-muted-foreground">
                                    Route this verified request to <strong>{request.target_institution}</strong>.
                                </p>
                                <Button onClick={handleRoute}>
                                    Route Request
                                </Button>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

VerificationShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Verification Queue', href: index.url() },
        { title: 'Review Request', href: '#' },
    ],
};
