<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import RecentActivities from '@/components/RecentActivities.vue';
import Schedule from '@/components/Schedule.vue';
import StatusSummary from '@/components/StatusSummary.vue';
import { Breadcrumb, BreadcrumbItem as CrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import type { Activity } from '@/types';
import { Head, usePage, usePoll } from '@inertiajs/vue3';
import { FolderOpen } from 'lucide-vue-next';
import { computed } from 'vue';

type RawPanelist = {
    first_name?: string;
    last_name?: string;
    name?: string;
    department?:
        | {
              name?: string;
          }
        | string;
};

type ScheduleData = {
    id: number;
    defense_date: string;
    defense_time: string;
    venue: string | null;
    panelists?: RawPanelist[];
    project?: {
        title: string;
    };
};

type FacultyDashboardPageProps = {
    activities?: Activity[];
    schedule?: ScheduleData | null;
    submitted?: number;
    under_review?: number;
    needs_revision?: number;
    completed?: number;
    has_projects?: boolean;
};

const page = usePage<FacultyDashboardPageProps>();

const activities = computed<Activity[]>(() => page.props.activities ?? []);
const schedule = computed<ScheduleData | null>(() => page.props.schedule ?? null);
const submitted = computed<number>(() => Number(page.props.submitted ?? 0));
const underReview = computed<number>(() => Number(page.props.under_review ?? 0));
const needsRevision = computed<number>(() => Number(page.props.needs_revision ?? 0));
const completed = computed<number>(() => Number(page.props.completed ?? 0));
const hasAssignedProject = computed<boolean>(() => page.props.has_projects === true);

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

usePoll(3000, {
    only: ['activities', 'schedule', 'submitted', 'under_review', 'needs_revision', 'completed', 'has_projects'],
});
</script>

<template>
    <Head title="Faculty Dashboard" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem>Overview</CrumbItem>
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

                <div v-if="!hasAssignedProject" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FolderOpen />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Projects Assigned</EmptyTitle>
                        <EmptyDescription>
                            Once a project is assigned to you, it will appear here.
                        </EmptyDescription>
                        <EmptyContent />
                    </Empty>
                </div>

                <template v-else>
                    <StatusSummary
                        :submitted="submitted"
                        :under_review="underReview"
                        :needs_revision="needsRevision"
                        :completed="completed"
                    />

                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                        <div class="xl:col-span-2">
                            <RecentActivities :activities="activities" />
                        </div>

                        <div class="xl:col-span-1">
                            <Schedule :schedule="schedule" />
                        </div>
                    </div>
                </template>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>