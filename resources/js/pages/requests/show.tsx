import { Head, router } from '@inertiajs/react';
import { FileText, Paperclip } from 'lucide-react';
import { index, submit } from '@/actions/App/Http/Controllers/Requester/RequestController';
import { store as storeMessage } from '@/routes/requests/messages';
import Heading from '@/components/heading';
import RequestTypeBadge, { type RequestTypeData } from '@/components/request-type-badge';
import { MessageThread } from '@/components/message-thread';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Attachment = { id: number; original_name: string; mime_type?: string; size: number; created_at?: string };
type Message = { id: number; body: string; sender: string; is_mine: boolean; created_at: string };
type ReleasedResponse = { notes: string | null; released_at: string; attachments: Attachment[] };

type RequestDetail = {
    id: number;
    reference_number: string;
    additional_context: string | null;
    request_type: RequestTypeData;
    nature_of_case: string | null;
    status: string;
    agency: string;
    target_institution: string;
    submitted_at: string | null;
    legal_process_signed_at: string | null;
    warrant_expires_at: string | null;
    request_due_at: string | null;
    created_at: string;
    attachments: Attachment[];
    response: ReleasedResponse | null;
    messages: Message[];
    can: { submit: boolean; send_message: boolean };
};

const STATUS_LABELS: Record<string, string> = {
    draft: 'Draft', submitted: 'Submitted', under_verification: 'Under Verification',
    verification_rejected: 'Rejected', verified: 'Verified', routed: 'Routed',
    assigned: 'Assigned', in_progress: 'In Progress', pending_manager_review: 'Pending Review',
    revision_requested: 'Revision Requested', approved: 'Approved', released: 'Released', closed: 'Closed',
};

function formatBytes(bytes: number): string {
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function RequestsShow({ request }: { request: RequestDetail }) {
    return (
        <>
            <Head title={request.reference_number} />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <Heading title={request.reference_number} />
                    <div className="flex items-center gap-3">
                        <Badge>{STATUS_LABELS[request.status] ?? request.status}</Badge>
                        {request.can.submit && (
                            <Button onClick={() => router.post(submit.url(request.id))}>
                                Submit Request
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-4 rounded-xl border p-5">
                        <p className="text-sm font-semibold">Request Details</p>
                        <dl className="space-y-3 text-sm">
                            {[
                                ['Type', <RequestTypeBadge type={request.request_type} />],
                                ...(request.nature_of_case ? [['Nature of Case', request.nature_of_case]] : []),
                                ['Agency', request.agency],
                                ['Target Institution', request.target_institution],
                                ['Submitted', request.submitted_at ? new Date(request.submitted_at).toLocaleDateString() : '—'],
                                ['Created', new Date(request.created_at).toLocaleDateString()],
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

                {request.response && (
                    <div className="rounded-xl border border-green-200 bg-green-50 p-5 dark:border-green-900 dark:bg-green-950/20">
                        <p className="mb-3 text-sm font-semibold text-green-800 dark:text-green-300">
                            Response Available — {new Date(request.response.released_at).toLocaleDateString()}
                        </p>
                        {request.response.notes && (
                            <p className="mb-3 text-sm text-muted-foreground">{request.response.notes}</p>
                        )}
                        {request.response.attachments.length > 0 && (
                            <ul className="space-y-1">
                                {request.response.attachments.map((att) => (
                                    <li key={att.id} className="flex items-center gap-2 text-sm">
                                        <FileText className="h-4 w-4 text-green-600" />
                                        <span className="font-medium">{att.original_name}</span>
                                        <span className="text-muted-foreground">{formatBytes(att.size)}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                )}

                <MessageThread
                    messages={request.messages}
                    postUrl={storeMessage.url(request.id)}
                    canSend={request.can.send_message}
                />
            </div>
        </>
    );
}

RequestsShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Requests', href: index.url() },
        { title: 'Request Detail', href: '#' },
    ],
};
