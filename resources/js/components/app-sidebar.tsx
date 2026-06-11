import { Link, usePage } from '@inertiajs/react';
import { BookOpen, ClipboardList, FilePlus, FileText, FolderGit2, LayoutGrid } from 'lucide-react';
import { index as requestsIndex, create as requestsCreate } from '@/actions/App/Http/Controllers/Requester/RequestController';
import { index as verificationIndex } from '@/actions/App/Http/Controllers/AmlakasVerifier/VerificationController';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const isRequester = auth.memberships?.some((m) => m.role === 'requester') ?? false;
    const isVerifier = auth.memberships?.some((m) => m.role === 'amlakas_verifier') ?? false;

    const mainNavItems: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        ...(isRequester
            ? [
                  { title: 'Requests', href: requestsIndex.url(), icon: FileText },
                  { title: 'New Request', href: requestsCreate.url(), icon: FilePlus },
              ]
            : []),
        ...(isVerifier
            ? [{ title: 'Verification Queue', href: verificationIndex.url(), icon: ClipboardList }]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
