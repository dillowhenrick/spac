import { Head, useForm } from '@inertiajs/react';
import { index, store } from '@/actions/App/Http/Controllers/Requester/RequestController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Institution = { id: number; name: string; code: string };
type RequestType = { value: string; label: string; description: string };
type NatureOption = { value: string; label: string };

const WARRANT_TYPES = ['search_warrant_domestic_us', 'pen_register_trap_trace'];

export default function RequestsCreate({
    institutions,
    requestTypes,
    natureOfCaseOptions,
}: {
    institutions: Institution[];
    requestTypes: RequestType[];
    natureOfCaseOptions: NatureOption[];
}) {
    const { data, setData, post, processing, errors } = useForm<{
        request_type: string;
        nature_of_case: string;
        reference_number: string;
        target_institution_id: string;
        legal_process_signed_at: string;
        warrant_expires_at: string;
        records_from: string;
        records_to: string;
        additional_context: string;
        attachments: File[];
    }>({
        request_type: '',
        nature_of_case: '',
        reference_number: '',
        target_institution_id: '',
        legal_process_signed_at: '',
        warrant_expires_at: '',
        records_from: '',
        records_to: '',
        additional_context: '',
        attachments: [],
    });

    const isWarrantType = WARRANT_TYPES.includes(data.request_type);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(store.url());
    }

    return (
        <>
            <Head title="New Request" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <Heading title="New Request" description="Submit a new legal process request." />

                <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="request_type">Legal Process</Label>
                        <Select
                            value={data.request_type}
                            onValueChange={(v) => setData('request_type', v)}
                        >
                            <SelectTrigger id="request_type">
                                <SelectValue placeholder="Select legal process..." />
                            </SelectTrigger>
                            <SelectContent>
                                {requestTypes.map((t) => (
                                    <SelectItem key={t.value} value={t.value}>
                                        <span className="font-medium">{t.label}</span>
                                        <span className="block text-xs text-muted-foreground">{t.description}</span>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.request_type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="nature_of_case">Nature of Case</Label>
                        <Select
                            value={data.nature_of_case}
                            onValueChange={(v) => setData('nature_of_case', v)}
                        >
                            <SelectTrigger id="nature_of_case">
                                <SelectValue placeholder="Select nature of case..." />
                            </SelectTrigger>
                            <SelectContent>
                                {natureOfCaseOptions.map((n) => (
                                    <SelectItem key={n.value} value={n.value}>
                                        {n.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.nature_of_case} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="reference_number">Reference Number</Label>
                        <Input
                            id="reference_number"
                            name="reference_number"
                            placeholder="e.g. WDCD-2026-0001"
                            value={data.reference_number}
                            onChange={(e) => setData('reference_number', e.target.value)}
                        />
                        <InputError message={errors.reference_number} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="target_institution_id">Target Institution</Label>
                        <Select
                            value={data.target_institution_id}
                            onValueChange={(v) => setData('target_institution_id', v)}
                        >
                            <SelectTrigger id="target_institution_id">
                                <SelectValue placeholder="Select institution..." />
                            </SelectTrigger>
                            <SelectContent>
                                {institutions.map((inst) => (
                                    <SelectItem key={inst.id} value={String(inst.id)}>
                                        {inst.name} ({inst.code})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.target_institution_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="legal_process_signed_at">Date Signed</Label>
                        <Input
                            id="legal_process_signed_at"
                            type="date"
                            value={data.legal_process_signed_at}
                            onChange={(e) => setData('legal_process_signed_at', e.target.value)}
                        />
                        <InputError message={errors.legal_process_signed_at} />
                    </div>

                    {isWarrantType && (
                        <div className="grid gap-2">
                            <Label htmlFor="warrant_expires_at">Warrant Expiry Date</Label>
                            <Input
                                id="warrant_expires_at"
                                type="date"
                                value={data.warrant_expires_at}
                                onChange={(e) => setData('warrant_expires_at', e.target.value)}
                            />
                            <InputError message={errors.warrant_expires_at} />
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="records_from">Records Beginning</Label>
                            <Input
                                id="records_from"
                                type="date"
                                value={data.records_from}
                                onChange={(e) => setData('records_from', e.target.value)}
                            />
                            <InputError message={errors.records_from} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="records_to">Records Ending</Label>
                            <Input
                                id="records_to"
                                type="date"
                                value={data.records_to}
                                onChange={(e) => setData('records_to', e.target.value)}
                            />
                            <InputError message={errors.records_to} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="additional_context">Additional Context</Label>
                        <textarea
                            id="additional_context"
                            name="additional_context"
                            rows={5}
                            placeholder="Provide any additional context or description..."
                            value={data.additional_context}
                            onChange={(e) => setData('additional_context', e.target.value)}
                            className="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={errors.additional_context} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="attachments">Attachments</Label>
                        <input
                            id="attachments"
                            type="file"
                            multiple
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                            onChange={(e) =>
                                setData('attachments', Array.from(e.target.files ?? []))
                            }
                            className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <p className="text-xs text-muted-foreground">
                            PDF, Word, or image files. Max 20MB each.
                        </p>
                        <InputError message={errors.attachments} />
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save as Draft'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href={index.url()}>Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

RequestsCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Requests', href: index.url() },
        { title: 'New Request', href: store.url() },
    ],
};
