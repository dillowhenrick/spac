import { Head, router, useForm } from '@inertiajs/react';
import { Paperclip } from 'lucide-react';
import {
    approveResponse,
    assign,
    assignSelf,
    queue,
    rejectResponse,
    releaseResponse,
} from '@/actions/App/Http/Controllers/Institution/ManagerController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import RequestTypeBadge, { type RequestTypeData } from '@/components/request-type-badge';
import { MessageThread } from '@/components/message-thread';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { store as storeMessage } from '@/routes/institution/messages';

type StaffMember = { id: number; name: string };
type Assignment = { id: number; assigned_to: { id: number; name: string }; is_self_assigned: boolean; assigned_at: string };
type Attachment = { id: number; original_name: string; size: number };
type ResponseData = { id: number; status: string; notes: string | null; submitted_at: string | null; attachments: Attachment[] };

type Message = { id: number; body: string; sender: string; is_mine: boolean; created_at: string };

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
    assignments: Assignment[];
    response: ResponseData | null;
    messages: Message[];
    can: { assign: boolean; approve_response: boolean; reject_response: boolean; release_response: boolean };
};

function formatBytes(bytes: number) {
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function InstitutionShow({ request, staff }: { request: RequestDetail; staff: StaffMember[] }) {
    const assignForm = useForm<{ staff_ids: number[] }>({ staff_ids: [] });
    const rejectForm = useForm({ notes: '' });

    function toggleStaff(id: number, checked: boolean) {
        assignForm.setData('staff_ids', checked
            ? [...assignForm.data.staff_ids, id]
            : assignForm.data.staff_ids.filter((s) => s !== id));
    }

    function handleAssign(e: React.FormEvent) {
        e.preventDefault();
        assignForm.post(assign.url(request.id));
    }

    function handleAssignSelf() {
        assignForm.post(assignSelf.url(request.id));
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
                                ['Institution', request.target_institution],
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

                {request.assignments.length > 0 && (
                    <div className="rounded-xl border p-5">
                        <p className="mb-3 text-sm font-semibold">Current Assignments</p>
                        <ul className="space-y-2">
                            {request.assignments.map((a) => (
                                <li key={a.id} className="flex items-center gap-2 text-sm">
                                    <span className="font-medium">{a.assigned_to.name}</span>
                                    {a.is_self_assigned && (
                                        <Badge variant="outline" className="text-xs">Self</Badge>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {request.can.assign && (
                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="space-y-4 rounded-xl border p-5">
                            <p className="text-sm font-semibold">Assign to Self</p>
                            <p className="text-sm text-muted-foreground">
                                Handle this request personally.
                            </p>
                            <Button onClick={handleAssignSelf} disabled={assignForm.processing}>
                                Assign to Me
                            </Button>
                        </div>

                        <form onSubmit={handleAssign} className="space-y-4 rounded-xl border p-5">
                            <p className="text-sm font-semibold">Assign to Staff</p>
                            <div className="space-y-2">
                                {staff.map((member) => (
                                    <div key={member.id} className="flex items-center gap-2">
                                        <Checkbox
                                            id={`staff-${member.id}`}
                                            checked={assignForm.data.staff_ids.includes(member.id)}
                                            onCheckedChange={(checked) => toggleStaff(member.id, Boolean(checked))}
                                        />
                                        <Label htmlFor={`staff-${member.id}`}>{member.name}</Label>
                                    </div>
                                ))}
                            </div>
                            <InputError message={assignForm.errors.staff_ids} />
                            <Button type="submit" disabled={assignForm.processing || assignForm.data.staff_ids.length === 0}>
                                Assign Selected
                            </Button>
                        </form>
                    </div>
                )}

                {request.response && (
                    <div className="rounded-xl border p-5">
                        <div className="mb-3 flex items-center justify-between">
                            <p className="text-sm font-semibold">Staff Response</p>
                            <Badge variant={request.response.status === 'pending_approval' ? 'default' : 'outline'}>
                                {request.response.status.replace(/_/g, ' ')}
                            </Badge>
                        </div>
                        {request.response.notes && (
                            <p className="mb-3 text-sm text-muted-foreground">{request.response.notes}</p>
                        )}
                        {request.response.attachments.length > 0 && (
                            <ul className="divide-y">
                                {request.response.attachments.map((att) => (
                                    <li key={att.id} className="flex items-center gap-2 py-2 text-sm">
                                        <Paperclip className="h-4 w-4 text-muted-foreground" />
                                        <span>{att.original_name}</span>
                                        <span className="text-muted-foreground">{formatBytes(att.size)}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                )}

                {(request.can.approve_response || request.can.reject_response || request.can.release_response) && (
                    <div className="grid gap-6 md:grid-cols-2">
                        {request.can.approve_response && (
                            <div className="space-y-3 rounded-xl border p-5">
                                <p className="text-sm font-semibold">Approve Response</p>
                                <p className="text-sm text-muted-foreground">Accept staff response and prepare for release.</p>
                                <Button onClick={() => router.post(approveResponse.url(request.id))}>
                                    Approve Response
                                </Button>
                            </div>
                        )}

                        {request.can.reject_response && (
                            <form onSubmit={(e) => { e.preventDefault(); rejectForm.post(rejectResponse.url(request.id)); }} className="space-y-3 rounded-xl border p-5">
                                <p className="text-sm font-semibold">Return for Revision</p>
                                <div className="grid gap-2">
                                    <Label htmlFor="reject-notes">Reason for revision</Label>
                                    <textarea
                                        id="reject-notes"
                                        rows={3}
                                        value={rejectForm.data.notes}
                                        onChange={(e) => rejectForm.setData('notes', e.target.value)}
                                        className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        placeholder="Explain what needs to be revised..."
                                    />
                                    <InputError message={rejectForm.errors.notes} />
                                </div>
                                <Button type="submit" variant="outline" disabled={rejectForm.processing}>
                                    Return to Staff
                                </Button>
                            </form>
                        )}

                        {request.can.release_response && (
                            <div className="space-y-3 rounded-xl border border-green-200 bg-green-50 p-5 dark:border-green-900 dark:bg-green-950/20">
                                <p className="text-sm font-semibold">Release Response</p>
                                <p className="text-sm text-muted-foreground">
                                    Release the approved response to the requester. This action is final.
                                </p>
                                <Button onClick={() => router.post(releaseResponse.url(request.id))}>
                                    Release to Requester
                                </Button>
                            </div>
                        )}
                    </div>
                )}

                <MessageThread
                    messages={request.messages ?? []}
                    postUrl={storeMessage.url(request.id)}
                    canSend={true}
                />
            </div>
        </>
    );
}

InstitutionShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Institution Queue', href: queue.url() },
        { title: 'Request Detail', href: '#' },
    ],
};
