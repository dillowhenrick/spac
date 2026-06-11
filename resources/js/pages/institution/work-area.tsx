import { Head, router, useForm } from '@inertiajs/react';
import { Paperclip } from 'lucide-react';
import { index, storeResponse, submitResponse } from '@/actions/App/Http/Controllers/Institution/StaffController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import RequestTypeBadge, { type RequestTypeData } from '@/components/request-type-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Attachment = { id: number; original_name: string; size: number };
type ResponseData = { id: number; status: string; notes: string | null; submitted_at: string | null; attachments: Attachment[] };

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
    response: ResponseData | null;
    can: { draft_response: boolean; submit_response: boolean };
};

function formatBytes(bytes: number) {
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function InstitutionWorkArea({ request }: { request: RequestDetail }) {
    const responseForm = useForm<{ notes: string; attachments: File[] }>({
        notes: request.response?.notes ?? '',
        attachments: [],
    });

    function handleSaveDraft(e: React.FormEvent) {
        e.preventDefault();
        responseForm.post(storeResponse.url(request.id));
    }

    function handleSubmit() {
        router.post(submitResponse.url(request.id));
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
                            ].map(([label, value]) => (
                                <div key={String(label)} className="flex justify-between">
                                    <dt className="text-muted-foreground">{label}</dt>
                                    <dd>{value}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>

                    {request.request_due_at && (
                        <div className="space-y-4 rounded-xl border border-destructive/30 bg-destructive/5 p-5">
                            <p className="text-sm font-semibold">Return Deadline</p>
                            <p className="text-lg font-bold text-destructive">
                                {new Date(request.request_due_at).toLocaleDateString()}
                            </p>
                            <p className="text-xs text-muted-foreground">48-hour return requirement (A.M. No. 17-11-03-SC)</p>
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
                        <p className="mb-4 text-sm font-semibold">Request Documents ({request.attachments.length})</p>
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

                {request.can.draft_response && request.response?.status !== 'pending_approval' && (
                    <form onSubmit={handleSaveDraft} className="space-y-4 rounded-xl border p-5">
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-semibold">Response Draft</p>
                            {request.response && (
                                <Badge variant="outline">Saved draft</Badge>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Internal Notes</Label>
                            <textarea
                                id="notes"
                                rows={5}
                                value={responseForm.data.notes}
                                onChange={(e) => responseForm.setData('notes', e.target.value)}
                                placeholder="Add notes for the manager..."
                                className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            />
                            <InputError message={responseForm.errors.notes} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="attachments">Response Documents</Label>
                            <input
                                id="attachments"
                                type="file"
                                multiple
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                onChange={(e) => responseForm.setData('attachments', Array.from(e.target.files ?? []))}
                                className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            />
                            <InputError message={responseForm.errors.attachments} />
                        </div>

                        {request.response?.attachments && request.response.attachments.length > 0 && (
                            <div>
                                <p className="mb-2 text-xs font-medium text-muted-foreground">Uploaded files:</p>
                                <ul className="space-y-1">
                                    {request.response.attachments.map((att) => (
                                        <li key={att.id} className="flex items-center gap-2 text-sm">
                                            <Paperclip className="h-3 w-3 text-muted-foreground" />
                                            <span>{att.original_name}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        <div className="flex gap-3">
                            <Button type="submit" variant="outline" disabled={responseForm.processing}>
                                Save Draft
                            </Button>
                            {request.can.submit_response && (
                                <Button type="button" onClick={handleSubmit}>
                                    Submit to Manager
                                </Button>
                            )}
                        </div>
                    </form>
                )}

                {request.response?.status === 'pending_approval' && (
                    <div className="rounded-xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-900 dark:bg-blue-950/20">
                        <p className="text-sm font-semibold">Response Submitted</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Your response was submitted for manager review on{' '}
                            {request.response.submitted_at
                                ? new Date(request.response.submitted_at).toLocaleDateString()
                                : '—'}.
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}

InstitutionWorkArea.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'My Assignments', href: index.url() },
        { title: 'Work Area', href: '#' },
    ],
};
