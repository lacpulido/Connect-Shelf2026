<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import RecentActivities from '@/components/RecentActivities.vue';
import ScheduleCard from '@/components/Schedule.vue';
import StatusSummary from '@/components/StatusSummary.vue';
import { Breadcrumb, BreadcrumbItem as CrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import type { Project } from '@/types/project';
import { Head, Link, usePage, usePoll } from '@inertiajs/vue3';
import { CheckCircle2, FileText, FolderOpen, Hourglass } from 'lucide-vue-next';
import { computed } from 'vue';

type Panelist = {
    name: string;
    department?: string;
};

type ScheduleType = {
    id?: number;
    defense_date: string;
    defense_time: string;
    venue: string | null;
    status?: string;
    panelists?: Panelist[];
    project?: {
        title?: string;
    };
};

type PageProps = {
    project: Project | null;
    schedule: ScheduleType | null;
    activities: any[];
    statusSummary: {
        submitted: number;
        under_review: number;
        needs_revision: number;
        completed: number;
    };
    canCreateProject: boolean;
    hasAnyProject: boolean;
    auth?: {
        user?: {
            first_name?: string;
        } | null;
    };
};

const page = usePage<PageProps>();

const project = computed(() => page.props.project ?? null);
const schedule = computed(() => page.props.schedule ?? null);
const activities = computed(() => page.props.activities ?? []);
const canCreateProject = computed(() => page.props.canCreateProject ?? false);
const hasAnyProject = computed(() => page.props.hasAnyProject ?? false);

const userName = computed(() => {
    return page.props.auth?.user?.first_name ?? 'User';
});

const statusSummary = computed(() => {
    return page.props.statusSummary ?? {
        submitted: 0,
        under_review: 0,
        needs_revision: 0,
        completed: 0,
    };
});

const completedProject = computed(() => {
    if (!project.value) return false;

    return (
        String(project.value.status ?? '').toLowerCase() === 'completed' ||
        !!project.value.completed_at
    );
});

const waitingApproval = computed(() => {
    return !!project.value && project.value.adviser_id === null && !completedProject.value;
});

const approved = computed(() => {
    return !!project.value && project.value.adviser_id !== null && !completedProject.value;
});

const scheduleData = computed(() => {
    if (!schedule.value) return null;

    return {
        ...schedule.value,
        panelists: schedule.value.panelists ?? [],
        project: {
            title: schedule.value.project?.title || project.value?.title || 'N/A',
        },
    };
});

usePoll(2000, {
    only: ['project', 'schedule', 'activities', 'statusSummary', 'canCreateProject', 'hasAnyProject'],
});
</script>

<template>
    <Head title="Dashboard" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem class="font">Overview</CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <!-- NO PROJECT YET -->
                <div v-if="!hasAnyProject" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FolderOpen />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Projects Yet</EmptyTitle>
                        <EmptyDescription>
                            Start by creating your first research project.
                        </EmptyDescription>

                        <EmptyContent>
                            <Link
                                v-if="canCreateProject"
                                :href="route('student.projects.create')"
                                class="inline-flex items-center justify-center rounded-full bg-[#0C4B05] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a3d04]"
                            >
                                Create Project
                            </Link>
                        </EmptyContent>
                    </Empty>
                </div>

                <!-- PROJECT RECORD FOUND BUT NO ACTIVE PROJECT -->
                <div v-else-if="!project" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileText />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>Project Record Found</EmptyTitle>
                        <EmptyDescription>
                            You already created a project before. You can only create one project.
                        </EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>

                <!-- WAITING APPROVAL -->
                <div v-else-if="waitingApproval" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Hourglass />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>Project Submitted</EmptyTitle>
                        <EmptyDescription>
                            Please wait for your adviser or department approval.
                        </EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>

                <!-- PROJECT COMPLETED -->
                <div v-else-if="completedProject" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <CheckCircle2 />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>Project Completed</EmptyTitle>
                        <EmptyDescription>
                            Your project has already been completed.
                        </EmptyDescription>

                        <EmptyContent>
                            <p class="text-sm font-medium text-gray-700">
                                Completed
                            </p>
                        </EmptyContent>
                    </Empty>
                </div>

                <!-- MAIN DASHBOARD -->
                <div v-else-if="approved" class="space-y-6">
                    <div class="rounded-2xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
                        <h2 class="text-2xl font-semibold text-gray-900">
                            Welcome back, {{ userName }} 👋
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Here’s what’s happening with your research project.
                        </p>
                    </div>

                    <StatusSummary
                        :submitted="statusSummary.submitted"
                        :under_review="statusSummary.under_review"
                        :needs_revision="statusSummary.needs_revision"
                        :completed="statusSummary.completed"
                    />

                    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
                        <div class="max-h-[420px] overflow-y-auto lg:col-span-2">
                            <RecentActivities :activities="activities" />
                        </div>

                        <div>
                            <ScheduleCard :schedule="scheduleData" />
                        </div>
                    </div>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>