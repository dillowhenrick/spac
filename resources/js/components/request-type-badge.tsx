import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

export type RequestTypeData = { value: string; label: string; description: string };

const COLOR_MAP: Record<string, string> = {
    emergency: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-300 dark:border-red-800',
    search_warrant_domestic_us: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800',
    pen_register_trap_trace: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800',
    subpoena: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
    court_order_domestic_us: 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800',
    court_order_outside_us: 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800',
    mlat: 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800',
    production_order: 'bg-teal-100 text-teal-700 border-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800',
};

export default function RequestTypeBadge({ type }: { type: RequestTypeData | undefined | null }) {
    if (!type) return null;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span className={`inline-flex cursor-default items-center rounded-md border px-2 py-0.5 text-xs font-medium ${COLOR_MAP[type.value] ?? 'border-border bg-muted text-muted-foreground'}`}>
                    {type.label}
                </span>
            </TooltipTrigger>
            <TooltipContent>{type.description}</TooltipContent>
        </Tooltip>
    );
}
