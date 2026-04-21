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
import { CheckCircle2, Clock3, FileCheck, FolderOpen } from 'lucide-vue-next';
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
};

const page = usePage<PageProps>();

const project = computed(() => page.props.project ?? null);
const schedule = computed(() => page.props.schedule ?? null);
const activities = computed(() => page.props.activities ?? []);
const canCreateProject = computed(() => page.props.canCreateProject ?? false);
const hasAnyProject = computed(() => page.props.hasAnyProject ?? false);

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

    return String(project.value.status ?? '').toLowerCase() === 'completed'
        || !!project.value.completed_at;
});

const waitingApproval = computed(() => {
    return !!project.value &&
        project.value.adviser_id === null &&
        !completedProject.value;
});

const approved = computed(() => {
    return !!project.value &&
        project.value.adviser_id !== null &&
        !completedProject.value;
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

const downloadReady = computed(() => {
    const url = new URL(window.location.href);
    return url.searchParams.get('download_ready') === '1';
});

const pendingDownloadUrl = computed(() => {
    const url = new URL(window.location.href);
    return url.searchParams.get('download_url') || '';
});

const clearDownloadParams = () => {
    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete('download_ready');
    cleanUrl.searchParams.delete('download_url');

    window.history.replaceState(
        {},
        '',
        cleanUrl.pathname + (cleanUrl.searchParams.toString() ? `?${cleanUrl.searchParams.toString()}` : ''),
    );
};

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
                <div
                    v-if="downloadReady && pendingDownloadUrl"
                    class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5 shadow-sm"
                >
                    <h2 class="text-lg font-semibold text-gray-900">Your file is ready</h2>
                    <p class="mt-1 text-sm text-gray-700">
                        Click below to download your manuscript.
                    </p>

                    <a
                        :href="pendingDownloadUrl"
                        @click="clearDownloadParams"
                        class="mt-4 inline-flex rounded-xl bg-[#0C4B05] px-5 py-3 text-white transition hover:opacity-90"
                    >
                        Download File
                    </a>
                </div>

                <div v-if="!hasAnyProject" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FolderOpen />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Project Yet</EmptyTitle>
                        <EmptyDescription>
                            Start by creating your first project.
                        </EmptyDescription>

                        <EmptyContent>
                            <Link
                                v-if="canCreateProject"
                                :href="route('student.projects.create')"
                                class="inline-flex rounded-xl bg-[#0C4B05] px-5 py-3 text-sm font-medium text-white transition hover:opacity-90"
                            >
                                Create Project
                            </Link>
                        </EmptyContent>
                    </Empty>
                </div>

                <div v-else-if="!project" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileCheck />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>Project Record Found</EmptyTitle>
                        <EmptyDescription>
                            You already created a project before. You can only create one project.
                        </EmptyDescription>
                        <EmptyContent />
                    </Empty>
                </div>

                <div v-else-if="waitingApproval" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Clock3 />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>Project Submitted</EmptyTitle>
                        <EmptyDescription>
                            Please wait for approval.
                        </EmptyDescription>
                        <EmptyContent />
                    </Empty>
                </div>

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
                        <EmptyContent />
                    </Empty>
                </div>

                <div v-else-if="approved" class="space-y-6">
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