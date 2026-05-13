<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2, FileText, FolderOpen, Hourglass, Send, X, XCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

type AdviserDecision = 'accepted' | 'declined' | 'pending' | string;

type PreferredAdviser = {
    id: number;
    name: string;
    preference_order?: number | null;
    decision?: AdviserDecision | null;
    is_shared?: boolean;
    shared_at?: string | null;
    department?: {
        name?: string | null;
    } | null;
};

type Project = {
    id: number;
    slug: string;
    title: string;
    project_type?: string | null;
    academic_year?: string | null;
    semester?: string | null;
    status?: string | null;
    is_resubmitted?: boolean;
    resubmitted_at?: string | null;
    proposal_titles?: string[];
    proposal_files?: Array<string | null>;
    proposal_original_names?: Array<string | null>;
    proposal_file?: string | null;
    proposal_original_name?: string | null;
    approved_proposal_index?: number | null;
    preferred_advisers?: PreferredAdviser[];
    student?: {
        id: number;
        name: string;
        department?: {
            name?: string | null;
        } | null;
    } | null;
};

const props = defineProps<{
    projects: Project[];
}>();

const selectedProject = ref<Project | null>(null);

const baseCustomClass = {
    popup: 'rounded-xl',
    title: 'text-lg font-semibold',
    htmlContainer: 'text-sm',
    confirmButton: 'px-4 py-1.5 text-sm rounded-md',
};

const acceptedAdviser = (project: Project): PreferredAdviser | null => {
    return project.preferred_advisers?.find((adviser) => adviser.decision?.toLowerCase() === 'accepted') ?? null;
};

const modalAdvisers = (project: Project): PreferredAdviser[] => {
    const accepted = acceptedAdviser(project);

    return accepted ? [accepted] : (project.preferred_advisers ?? []);
};

const hasApprovedTitle = (project: Project): boolean => {
    return project.approved_proposal_index !== null && project.approved_proposal_index !== undefined;
};

const approvedTopic = (project: Project): string => {
    const index = project.approved_proposal_index;

    if (index === null || index === undefined) {
        return project.proposal_titles?.[0] ?? project.title ?? 'No approved topic yet';
    }

    return project.proposal_titles?.[index] ?? project.title ?? 'No approved topic yet';
};

const isProjectApproved = (project: Project): boolean => {
    return hasApprovedTitle(project);
};

const approvedProjectsCount = computed(() => {
    return props.projects.filter((project) => isProjectApproved(project)).length;
});

const formatDecision = (decision?: AdviserDecision | null): string => {
    const value = decision?.toLowerCase();

    if (value === 'accepted') return 'Accepted';
    if (value === 'declined' || value === 'rejected') return 'Declined';

    return 'Pending';
};

const decisionClass = (decision?: AdviserDecision | null): string => {
    const value = decision?.toLowerCase();

    if (value === 'accepted') return 'bg-green-50 text-[#0C4B05] ring-green-100';
    if (value === 'declined' || value === 'rejected') return 'bg-red-50 text-red-700 ring-red-100';

    return 'bg-amber-50 text-amber-700 ring-amber-100';
};

const decisionIcon = (decision?: AdviserDecision | null) => {
    const value = decision?.toLowerCase();

    if (value === 'accepted') return CheckCircle2;
    if (value === 'declined' || value === 'rejected') return XCircle;

    return Hourglass;
};

const getStorageUrl = (path?: string | null): string | null => {
    if (!path) return null;

    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    return `/storage/${path}`;
};

const getTopicFileUrl = (project: Project, index: number): string | null => {
    const file = project.proposal_files?.[index];

    if (file) return getStorageUrl(file);

    if (index === 0 && project.proposal_file) {
        return getStorageUrl(project.proposal_file);
    }

    return null;
};

const getTopicFileName = (project: Project, index: number): string => {
    const fileName = project.proposal_original_names?.[index];

    if (fileName) return fileName;

    if (index === 0 && project.proposal_original_name) {
        return project.proposal_original_name;
    }

    return `Topic ${index + 1} file`;
};

const shareProposalToAdviser = (project: Project, adviser: PreferredAdviser) => {
    router.post(
        route('focalperson.proposals.share-adviser', project.slug),
        {
            adviser_id: adviser.id,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                adviser.is_shared = true;

                Swal.fire({
                    icon: 'success',
                    title: 'Shared!',
                    text: `Full proposal was shared to ${adviser.name}.`,
                    confirmButtonColor: '#0C4B05',
                    confirmButtonText: 'OK',
                    width: 320,
                    padding: '1rem',
                    iconColor: '#16a34a',
                    customClass: {
                        ...baseCustomClass,
                    },
                });
            },
            onError: () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Unable to share',
                    text: 'Please check the selected adviser and try again.',
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'OK',
                    width: 320,
                    padding: '1rem',
                    customClass: {
                        ...baseCustomClass,
                    },
                });
            },
        },
    );
};

const selectedProposalTitle = computed(() => {
    if (!selectedProject.value) return null;

    return approvedTopic(selectedProject.value);
});

const openReview = (project: Project) => {
    selectedProject.value = project;
};

const closeReview = () => {
    selectedProject.value = null;
};
</script>

<template>
    <Head title="Proposals" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-4 md:px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem>Proposals</CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <main class="space-y-5 p-4 md:p-6">
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-5 shadow-sm md:px-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 md:text-2xl">Topic Proposals</h1>

                            <p class="mt-1 text-sm text-gray-500">
                                View approved topics, assigned advisers, and student leaders who resubmitted new topics.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="!props.projects.length" class="flex min-h-[55vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FolderOpen />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Proposals Found</EmptyTitle>

                        <EmptyDescription> Student proposals will appear here once submitted. </EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>

                <div v-else class="overflow-hidden rounded-xl border border-gray-200">
                    <div
                        class="hidden border-b border-gray-100 px-5 py-3 text-xs font-bold uppercase tracking-wide text-gray-500 md:grid md:grid-cols-[1.3fr_1.5fr_1.2fr_150px_120px] md:items-center md:gap-4"
                    >
                        <div>Student Leader</div>
                        <div class="text-center">Adviser</div>
                        <div class="text-center">Resubmitted</div>
                        <div class="text-center">Status</div>
                        <div class="text-center">Action</div>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="project in props.projects"
                            :key="project.id"
                            class="grid gap-4 px-5 py-4 md:grid-cols-[1.3fr_1.5fr_1.2fr_150px_120px] md:items-center md:gap-4"
                        >
                            <!-- Student -->
                            <div>
                                <p class="font-semibold text-gray-900">
                                    {{ project.student?.name ?? 'No student assigned' }}
                                </p>

                                <p class="mt-1 text-sm text-gray-500">
                                    {{ project.student?.department?.name ?? 'No department' }}
                                </p>
                            </div>

                            <!-- Adviser -->
                            <div class="text-center">
                                <span class="mb-1 block text-sm font-semibold text-gray-500 md:hidden"> Adviser: </span>

                                <template v-if="acceptedAdviser(project)">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ acceptedAdviser(project)?.name }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ acceptedAdviser(project)?.department?.name ?? 'No department' }}
                                    </p>
                                </template>

                                <p v-else class="text-sm text-gray-400">No accepted adviser</p>
                            </div>

                            <!-- Resubmitted -->
                            <div class="flex justify-center">
                                <span class="mb-1 block text-sm font-semibold text-gray-500 md:hidden"> Resubmitted: </span>

                                <span
                                    v-if="project.is_resubmitted"
                                    class="inline-flex min-w-[90px] items-center justify-center rounded-full px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-200"
                                >
                                    Yes
                                </span>

                                <span
                                    v-else
                                    class="inline-flex min-w-[90px] items-center justify-center rounded-full px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-200"
                                >
                                    No
                                </span>
                            </div>

                            <!-- Status -->
                            <div class="flex justify-center">
                                <span class="mb-1 block text-sm font-semibold text-gray-500 md:hidden"> Status: </span>

                                <span
                                    v-if="isProjectApproved(project)"
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-[#0C4B05] ring-1 ring-green-100"
                                >
                                    Approved
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-200"
                                >
                                    Pending
                                </span>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-center">
                                <button
                                    type="button"
                                    @click="openReview(project)"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-gray-700 transition hover:text-[#0C4B05] hover:underline"
                                >
                                    View
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <div v-if="selectedProject" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Project Details</h2>

                            <p class="mt-1 text-sm text-gray-500">Review adviser status, project information, and submitted files.</p>
                        </div>

                        <button
                            type="button"
                            @click="closeReview"
                            class="rounded-full p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
                            aria-label="Close modal"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="max-h-[75vh] space-y-5 overflow-y-auto p-5">
                        <!-- Project Info -->
                        <section class="rounded-xl border border-gray-200 p-4">
                            <!-- Label / Title -->
                            <div class="mb-4">
                                <h3 class="text-base font-semibold text-gray-900">Project Information</h3>

                                <p class="mt-1 text-xs text-gray-500">Overview of the student's project information.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                                <p>
                                    <span class="font-semibold text-gray-700">Student:</span>
                                    {{ selectedProject.student?.name ?? 'N/A' }}
                                </p>

                                <p>
                                    <span class="font-semibold text-gray-700">Department:</span>
                                    {{ selectedProject.student?.department?.name ?? 'N/A' }}
                                </p>

                                <p>
                                    <span class="font-semibold text-gray-700">Project Type:</span>
                                    {{ selectedProject.project_type ?? 'N/A' }}
                                </p>

                                <p>
                                    <span class="font-semibold text-gray-700">Academic Year:</span>
                                    {{ selectedProject.academic_year ?? 'N/A' }}
                                </p>

                                <p>
                                    <span class="font-semibold text-gray-700">Semester:</span>
                                    {{ selectedProject.semester ?? 'N/A' }}
                                </p>

                                <p>
                                    <span class="font-semibold text-gray-700">Status:</span>
                                    {{ isProjectApproved(selectedProject) ? 'Approved' : 'Pending' }}
                                </p>
                            </div>
                        </section>

                        <!-- Adviser Section -->
                        <section class="rounded-xl border border-gray-200 p-4">
                            <div class="mb-4 flex items-center gap-3">
                                <div>
                                    <h3 class="font-semibold text-gray-900">Adviser Decision</h3>

                                    <p class="text-xs text-gray-500">Share the proposal to advisers.</p>
                                </div>
                            </div>

                            <div v-if="modalAdvisers(selectedProject).length" class="space-y-3">
                                <div
                                    v-for="adviser in modalAdvisers(selectedProject)"
                                    :key="adviser.id"
                                    class="rounded-lg border border-gray-200 bg-white p-4"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <!-- Adviser Info -->
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ adviser.preference_order }}. {{ adviser.name }}</p>

                                            <p class="mt-1 text-sm text-gray-500">
                                                {{ adviser.department?.name ?? 'No department' }}
                                            </p>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-2">
                                            <!-- Share Button -->
                                            <button
                                                type="button"
                                                :disabled="adviser.is_shared"
                                                @click="shareProposalToAdviser(selectedProject, adviser)"
                                                :class="[
                                                    'inline-flex items-center justify-center rounded-md border p-2 transition',
                                                    adviser.is_shared
                                                        ? 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400'
                                                        : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50',
                                                ]"
                                            >
                                                <Send class="h-4 w-4" />
                                            </button>

                                            <!-- Status Badge -->
                                            <span
                                                :class="[
                                                    'inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold',
                                                    adviser.decision === 'declined'
                                                        ? 'border-red-300 bg-white text-red-600'
                                                        : adviser.decision === 'approved'
                                                          ? 'border-green-300 bg-white text-green-600'
                                                          : 'border-gray-300 bg-white text-gray-600',
                                                ]"
                                            >
                                                <component
                                                    v-if="adviser.decision !== 'pending'"
                                                    :is="decisionIcon(adviser.decision)"
                                                    class="mr-1 h-3.5 w-3.5"
                                                />

                                                {{ formatDecision(adviser.decision) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p v-else class="text-sm text-gray-500">No preferred adviser selected.</p>
                        </section>
                        <!-- Proposal Topics -->
                        <section class="rounded-2xl border border-gray-200 bg-white">
                            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-4 sm:px-5">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">Proposed Topics</h3>

                                    <p class="mt-1 text-xs text-gray-500">View submitted topics and uploaded proposal files.</p>
                                </div>
                            </div>

                            <div class="p-4 sm:p-5">
                                <div v-if="selectedProject.proposal_titles?.length" class="space-y-3">
                                    <div
                                        v-for="(topic, index) in selectedProject.proposal_titles"
                                        :key="index"
                                        class="rounded-xl border border-gray-200 p-4 transition"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Topic {{ index + 1 }}</p>

                                                <span
                                                    v-if="selectedProject.approved_proposal_index === index"
                                                    class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-semibold text-[#0C4B05]"
                                                >
                                                    Approved
                                                </span>
                                            </div>

                                            <p class="mt-1 text-sm font-semibold leading-6 text-gray-900">
                                                {{ topic || 'Untitled topic' }}
                                            </p>

                                            <div class="mt-3">
                                                <a
                                                    v-if="getTopicFileUrl(selectedProject, index)"
                                                    :href="getTopicFileUrl(selectedProject, index)!"
                                                    target="_blank"
                                                    class="inline-flex max-w-full items-center gap-2 text-sm font-medium text-[#0C4B05] underline underline-offset-2 hover:text-[#083604]"
                                                >
                                                    <FileText class="h-4 w-4 flex-shrink-0" />

                                                    <span class="truncate">
                                                        {{ getTopicFileName(selectedProject, index) }}
                                                    </span>
                                                </a>

                                                <span v-else class="inline-flex items-center gap-2 text-sm text-gray-400">
                                                    <FileText class="h-4 w-4" />
                                                    No File Uploaded
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <p v-else class="rounded-xl border border-dashed border-gray-300 p-5 text-center text-sm text-gray-500">
                                    No proposal topics submitted.
                                </p>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>
