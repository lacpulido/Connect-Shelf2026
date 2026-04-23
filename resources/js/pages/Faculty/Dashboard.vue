<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import RecentActivities from '@/components/RecentActivities.vue';
import StatusSummary from '@/components/StatusSummary.vue';
import { Breadcrumb, BreadcrumbItem as CrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import type { Activity } from '@/types';
import { Head, usePage, usePoll } from '@inertiajs/vue3';
import { FolderOpen } from 'lucide-vue-next';
import { computed } from 'vue';

type FacultyDashboardPageProps = {
    activities?: Activity[];
    submitted?: number;
    under_review?: number;
    needs_revision?: number;
    completed?: number;
    has_projects?: boolean;
    auth?: {
        user?: {
            id?: number;
            first_name?: string;
            last_name?: string;
            email?: string;
        } | null;
    };
};

const page = usePage<FacultyDashboardPageProps>();

const activities = computed(() => page.props.activities ?? []);

const submitted = computed(() => Number(page.props.submitted ?? 0));
const underReview = computed(() => Number(page.props.under_review ?? 0));
const needsRevision = computed(() => Number(page.props.needs_revision ?? 0));
const completed = computed(() => Number(page.props.completed ?? 0));
const hasAssignedProject = computed(() => page.props.has_projects === true);

// ✅ USE FIRST NAME FROM DATABASE
const userName = computed(() => {
    return page.props.auth?.user?.first_name ?? 'User';
});

usePoll(3000, {
    only: ['activities', 'submitted', 'under_review', 'needs_revision', 'completed', 'has_projects'],
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
                    <!-- WELCOME SECTION -->
                    <div class="rounded-2xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
                        <h2 class="text-2xl font-semibold text-gray-900">
                            Welcome back, {{ userName }} 👋
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Here’s what’s happening with your research projects.
                        </p>
                    </div>

                    <!-- KPI -->
                    <StatusSummary
                        :submitted="submitted"
                        :under_review="underReview"
                        :needs_revision="needsRevision"
                        :completed="completed"
                    />

                    <!-- RECENT ACTIVITIES FULL WIDTH -->
                    <div class="w-full">
                        <RecentActivities :activities="activities" />
                    </div>
                </template>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>