<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import RecentActivities from '@/components/RecentActivities.vue';
import ResubmitTopicsModal from '@/components/ResubmitTopicsModal.vue';
import ScheduleCard from '@/components/Schedule.vue';
import StatusSummary from '@/components/StatusSummary.vue';

import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

import type { Project } from '@/types/project';
import { Head, Link, usePage, usePoll } from '@inertiajs/vue3';
import { BarChart3, CalendarDays, FileText, FolderOpen, Pencil, User, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

type ProposalTitle = {
    index: number;
    title: string;
    remarks: string;
    is_approved?: boolean;
};

type SelectedAdviser = {
    id: number;
    rank: number;
    name: string;
    status: string;
    is_shared?: boolean;
    shared_at?: string | null;
};

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

type DashboardProject = Project & {
    id: number;
    slug: string;
    title: string;
    status: string;
    project_type: string;
    academic_year?: string | null;
    semester?: string | null;
    adviser_id?: number | null;
    adviser?: {
        name?: string;
    } | null;
    updated_at?: string | null;
    completed_at?: string | null;
    proposal_file?: string | null;
    proposal_original_name?: string | null;
    approved_proposal_index?: number | null;
    selected_advisers?: SelectedAdviser[];
    proposal_titles?: ProposalTitle[];
};

type PageProps = {
    project: DashboardProject | null;
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
    showWelcomeBanner: boolean;
    hideProposalBox: boolean;
    allAdvisersDeclined: boolean;
    auth?: {
        user?: {
            id?: number;
            first_name?: string;
        } | null;
    };
};

const page = usePage<PageProps>();

const showAdviserModal = ref(false);
const showResubmitModal = ref(false);

const userId = computed(() => page.props.auth?.user?.id ?? 'guest');

const welcomeBannerKey = computed(() => {
    return `student-dashboard-welcome-banner-${userId.value}`;
});

const showWelcomeBackBanner = ref(true);

onMounted(() => {
    const savedBannerState = localStorage.getItem(welcomeBannerKey.value);

    if (savedBannerState === null) {
        showWelcomeBackBanner.value = true;
        localStorage.setItem(welcomeBannerKey.value, 'true');
        return;
    }

    showWelcomeBackBanner.value = savedBannerState === 'true';
});

watch(showWelcomeBackBanner, (value) => {
    localStorage.setItem(welcomeBannerKey.value, String(value));
});

const project = computed(() => page.props.project ?? null);
const schedule = computed(() => page.props.schedule ?? null);
const activities = computed(() => page.props.activities ?? []);

const canCreateProject = computed(() => page.props.canCreateProject ?? false);
const hasAnyProject = computed(() => page.props.hasAnyProject ?? false);
const hideProposalBox = computed(() => page.props.hideProposalBox ?? false);
const allAdvisersDeclined = computed(() => page.props.allAdvisersDeclined ?? false);

const userName = computed(() => page.props.auth?.user?.first_name ?? 'User');

const statusSummary = computed(() => {
    return (
        page.props.statusSummary ?? {
            submitted: 0,
            under_review: 0,
            needs_revision: 0,
            completed: 0,
        }
    );
});

const dashboardKey = computed(() => {
    const projectId = project.value?.id ?? 'no-project';

    return `${userId.value}-${projectId}`;
});

const selectedAdvisers = computed(() => project.value?.selected_advisers ?? []);
const proposalTitles = computed(() => project.value?.proposal_titles ?? []);

const acceptedAdviser = computed(() => {
    return selectedAdvisers.value.find((adviser) => String(adviser.status).toLowerCase() === 'accepted') ?? null;
});

const completedProject = computed(() => {
    if (!project.value) return false;

    return String(project.value.status ?? '').toLowerCase() === 'completed' || !!project.value.completed_at;
});

const approved = computed(() => {
    if (!project.value || completedProject.value || allAdvisersDeclined.value) return false;

    const status = String(project.value.status ?? '').toLowerCase();

    const hasAdviser = (project.value.adviser_id !== null && project.value.adviser_id !== undefined) || !!acceptedAdviser.value;

    const hasApprovedTopic =
        (project.value.approved_proposal_index !== null && project.value.approved_proposal_index !== undefined) ||
        status === 'approved' ||
        status === 'accepted' ||
        status === 'ongoing';

    return hasAdviser && hasApprovedTopic;
});

const showProposalBox = computed(() => {
    return !!project.value && !approved.value && !completedProject.value && !hideProposalBox.value;
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

const adviserDecisionLabel = (status: string) => {
    const value = String(status ?? '').toLowerCase();

    if (value === 'accepted') return 'Accepted';
    if (value === 'declined') return 'Declined';

    return 'Pending';
};

usePoll(2000, {
    only: [
        'project',
        'schedule',
        'activities',
        'statusSummary',
        'canCreateProject',
        'hasAnyProject',
        'showWelcomeBanner',
        'hideProposalBox',
        'allAdvisersDeclined',
    ],
});
</script>

<template>
    <Head title="Dashboard" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-4 md:px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem class="text-black-900">Dashboard</CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div :key="dashboardKey" class="space-y-6 p-4 md:p-6">
                <div v-if="!hasAnyProject" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FolderOpen />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Topic Proposal Yet</EmptyTitle>
                        <EmptyDescription>Submit your 3 proposed titles first.</EmptyDescription>

                        <EmptyContent>
                            <Link
                                v-if="canCreateProject"
                                :href="route('student.projects.create')"
                                class="inline-flex items-center justify-center rounded-full bg-[#0C4B05] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a3d04]"
                            >
                                Submit Topic Proposal
                            </Link>
                        </EmptyContent>
                    </Empty>
                </div>

                <div v-else-if="!project" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileText />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>Project Record Found</EmptyTitle>
                        <EmptyDescription>You already created a project before. You can only create one project.</EmptyDescription>
                        <EmptyContent />
                    </Empty>
                </div>

                <div v-else class="mx-auto max-w-6xl space-y-6">
                    <div v-if="allAdvisersDeclined" class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm md:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex gap-3">
                                <XCircle class="mt-0.5 h-5 w-5 shrink-0 text-red-600" />

                                <div>
                                    <p class="text-sm font-semibold text-red-800">All Preferred Advisers Declined</p>
                                </div>
                            </div>

                            <button
                                v-if="canCreateProject"
                                type="button"
                                @click="showResubmitModal = true"
                                class="inline-flex w-fit items-center justify-center rounded-lg bg-[#0C4B05] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a3d04]"
                            >
                                Submit New Topics
                            </button>
                        </div>
                    </div>

                    <template v-if="showProposalBox">
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-6">
                            <h1 class="mt-1 text-base font-bold text-gray-900 md:text-lg">Submitted Proposals</h1>
                            <p class="mt-2 text-sm text-gray-500">View and manage all submitted research topic proposals from students.</p>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-100 bg-gray-50 px-5 py-4">
                                <h2 class="text-base font-bold text-gray-900">Proposed Topics</h2>
                                <p class="mt-1 text-sm text-gray-500">Submitted research titles and attached proposal document.</p>
                            </div>

                            <div v-if="proposalTitles.length" class="divide-y divide-gray-100">
                                <div v-for="proposal in proposalTitles" :key="proposal.index" class="p-5 transition hover:bg-gray-50">
                                    <div class="flex gap-4">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0C4B05]/10 text-sm font-bold text-[#0C4B05]">
                                            {{ proposal.index + 1 }}
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Proposed Topic {{ proposal.index + 1 }}
                                            </p>

                                            <h3 class="mt-1 break-words text-base font-semibold leading-6 text-gray-900">
                                                {{ proposal.title }}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="p-6 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                    <FileText class="h-5 w-5 text-gray-500" />
                                </div>

                                <p class="mt-3 text-sm font-semibold text-gray-700">No proposal titles found</p>
                                <p class="mt-1 text-sm text-gray-500">Submitted proposal titles will appear here once available.</p>
                            </div>

                            <div class="border-t border-gray-100 px-5 py-4">
                                <div class="flex justify-end">
                                    <button
                                        type="button"
                                        @click="showAdviserModal = true"
                                        class="inline-flex items-center justify-center rounded-lg border border-[#0C4B05] px-4 py-2 text-sm font-semibold text-[#0C4B05] transition hover:bg-[#0C4B05] hover:text-white"
                                    >
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template v-if="approved && project && !allAdvisersDeclined">
                        <div class="space-y-6">
                            <div
                                v-if="showWelcomeBackBanner"
                                class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm md:px-6"
                            >
                                <h2 class="text-xl font-semibold text-gray-900 md:text-2xl">
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

                            <div class="space-y-6">
                                <RecentActivities :activities="activities" />

                                <ScheduleCard :schedule="scheduleData" />

                                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                                    <div class="p-5 md:p-6">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <h2 class="break-words text-xl font-bold leading-snug text-gray-900 md:text-2xl">
                                                    {{ project.title }}
                                                </h2>

                                                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                                    <span>Last updated: {{ project.updated_at ?? 'N/A' }}</span>
                                                </div>
                                            </div>

                                            <Link
                                                :href="route('student.projects.edit', { project: project.slug })"
                                                class="inline-flex w-fit items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-[#0C4B05] hover:text-[#0C4B05]"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                Edit Project
                                            </Link>
                                        </div>

                                        <div class="my-6 border-t border-gray-100"></div>

                                        <div class="space-y-5">
                                            <div class="flex items-start gap-4 border-b border-gray-100 pb-5">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100">
                                                    <User class="h-5 w-5 text-gray-700" />
                                                </div>

                                                <div>
                                                    <p class="text-sm font-semibold text-gray-700">Adviser</p>
                                                    <p class="mt-1 text-sm text-gray-600">
                                                        {{ project.adviser?.name ?? acceptedAdviser?.name ?? 'Not Assigned' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex items-start gap-4 border-b border-gray-100 pb-5">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100">
                                                    <BarChart3 class="h-5 w-5 text-gray-700" />
                                                </div>

                                                <div>
                                                    <p class="text-sm font-semibold text-gray-700">Research Type</p>
                                                    <p class="mt-1 text-sm text-gray-600">
                                                        {{ project.project_type ?? 'N/A' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex items-start gap-4">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100">
                                                    <CalendarDays class="h-5 w-5 text-gray-700" />
                                                </div>

                                                <div>
                                                    <p class="text-sm font-semibold text-gray-700">Academic Year</p>
                                                    <p class="mt-1 text-sm text-gray-600">
                                                        {{ project.academic_year ?? 'N/A' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <Teleport to="body">
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="showAdviserModal"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
                        @click.self="showAdviserModal = false"
                    >
                        <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
                            <div class="flex items-start justify-between gap-4 border-b border-gray-100 bg-white px-5 py-5 sm:px-6">
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">Adviser Decisions</h2>
                                    <p class="mt-1 text-sm text-gray-500">View the current status of your preferred advisers.</p>
                                </div>

                                <button
                                    type="button"
                                    @click="showAdviserModal = false"
                                    class="flex h-11 w-11 items-center justify-center rounded-full text-2xl font-bold text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                >
                                    ×
                                </button>
                            </div>

                            <div class="max-h-[72vh] overflow-y-auto px-5 py-5 sm:px-6">
                                <div v-if="selectedAdvisers.length" class="space-y-4">
                                    <h3 class="text-sm font-bold uppercase tracking-wide text-gray-700">Selected Advisers</h3>

                                    <div class="grid gap-3">
                                        <div
                                            v-for="adviser in selectedAdvisers"
                                            :key="adviser.id"
                                            class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-gray-300 hover:shadow-md"
                                        >
                                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                                <div class="min-w-0">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                        Preferred Adviser #{{ adviser.rank }}
                                                    </p>

                                                    <h3 class="mt-1 truncate text-base font-bold text-gray-900">
                                                        {{ adviser.name }}
                                                    </h3>

                                                    <p v-if="adviser.shared_at" class="mt-1 text-xs text-gray-500">
                                                        Shared: {{ adviser.shared_at }}
                                                    </p>
                                                </div>

                                                <span
                                                    v-if="adviser.status === 'pending'"
                                                    class="inline-flex items-center rounded-full bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700"
                                                >
                                                    Pending
                                                </span>

                                                <span
                                                    v-else-if="adviser.status === 'accepted'"
                                                    class="inline-flex items-center rounded-full bg-[#0C4B05]/10 px-4 py-2 text-xs font-semibold text-[#0C4B05]"
                                                >
                                                    Accepted
                                                </span>

                                                <span
                                                    v-else-if="adviser.status === 'declined'"
                                                    class="inline-flex items-center rounded-full bg-red-100 px-4 py-2 text-xs font-semibold text-red-700"
                                                >
                                                    Declined
                                                </span>

                                                <span
                                                    v-else
                                                    class="inline-flex items-center rounded-full bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700"
                                                >
                                                    {{ adviserDecisionLabel(adviser.status) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="allAdvisersDeclined"
                                    class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm font-medium text-red-700"
                                >
                                    All advisers declined. You may now submit a new set of topic proposals.
                                </div>

                                <div
                                    v-if="!selectedAdvisers.length"
                                    class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-5 py-10 text-center"
                                >
                                    <h3 class="text-sm font-bold text-gray-900">No adviser decisions found</h3>
                                    <p class="mt-1 text-sm text-gray-500">Adviser decisions will appear here once reviewed.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </Teleport>

            <ResubmitTopicsModal
                v-if="project"
                :show="showResubmitModal"
                :project-slug="project.slug"
                @close="showResubmitModal = false"
            />
        </SidebarInset>
    </SidebarProvider>
</template>