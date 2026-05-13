<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { Head, router } from '@inertiajs/vue3';
import { Check, CheckCircle2, FolderOpen, UserCheck, X, XCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref } from 'vue';

type Project = {
    id: number;
    slug: string;
    title: string;
    status?: string | null;
    adviser_status?: string | null;
    is_shared?: boolean;
    shared_at?: string | null;
    has_accepted_adviser?: boolean;
    accepted_adviser_id?: number | null;
    accepted_adviser_name?: string | null;
    project_type?: string | null;
    academic_year?: string | null;
    semester?: string | null;
    proposal_titles?: string[];
    proposal_files?: Array<string | null>;
    proposal_original_names?: Array<string | null>;
    proposal_file?: string | null;
    proposal_original_name?: string | null;
    preferred_adviser_rank?: number | null;
    approved_proposal_index?: number | null;

    student?: {
        id: number;
        name: string;
        department?: {
            name?: string | null;
        } | null;
    } | null;

    researchers?: {
        id: number;
        name: string;
    }[];
};

defineProps<{
    projects: Project[];
}>();

const selectedProject = ref<Project | null>(null);
const selectedProposalIndex = ref<number | null>(null);

const baseCustomClass = {
    popup: 'rounded-xl',
    title: 'text-lg font-semibold',
    htmlContainer: 'text-sm',
    confirmButton: 'px-4 py-1.5 text-sm rounded-md',
    cancelButton: 'px-4 py-1.5 text-sm rounded-md',
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

const openReview = (project: Project) => {
    selectedProject.value = project;
    selectedProposalIndex.value = project.approved_proposal_index ?? null;
};

const closeReview = () => {
    selectedProject.value = null;
    selectedProposalIndex.value = null;
};

const isAcceptedByAnotherAdviser = (project: Project): boolean => {
    return Boolean(project.has_accepted_adviser && project.adviser_status !== 'accepted');
};

const canAcceptProject = (project: Project): boolean => {
    return (
        !isAcceptedByAnotherAdviser(project) &&
        project.adviser_status !== 'declined' &&
        project.adviser_status !== 'accepted' &&
        selectedProposalIndex.value !== null
    );
};

const acceptRequest = async (project: Project) => {
    if (!canAcceptProject(project) || selectedProposalIndex.value === null) return;

    const selectedTitle = project.proposal_titles?.[selectedProposalIndex.value] ?? 'this topic';

    const result = await Swal.fire({
        icon: 'question',
        title: 'Accept this topic?',
        html: `You are about to vote and accept <b>${selectedTitle}</b> as the approved topic.`,
        showCancelButton: true,
        confirmButtonText: 'Yes, accept',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0C4B05',
        cancelButtonColor: '#6b7280',
        width: 360,
        padding: '1rem',
        customClass: {
            ...baseCustomClass,
        },
    });

    if (!result.isConfirmed) return;

    router.post(
        route('faculty.adviser-requests.accept', {
            project: project.slug,
        }),
        {
            approved_proposal_index: selectedProposalIndex.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeReview();

                Swal.fire({
                    icon: 'success',
                    title: 'Topic Accepted',
                    text: 'You have successfully accepted this topic as adviser.',
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
                    title: 'Unable to Accept',
                    text: 'Something went wrong while accepting this topic. Please try again.',
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

const declineRequest = async (project: Project) => {
    if (project.adviser_status === 'accepted' || isAcceptedByAnotherAdviser(project)) {
        return;
    }

    const result = await Swal.fire({
        icon: 'warning',
        title: 'Decline request?',
        text: 'Are you sure you want to decline this adviser request?',
        showCancelButton: true,
        confirmButtonText: 'Yes, decline',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        width: 360,
        padding: '1rem',
        customClass: {
            ...baseCustomClass,
        },
    });

    if (!result.isConfirmed) return;

    router.post(
        route('faculty.adviser-requests.decline', {
            project: project.slug,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeReview();

                Swal.fire({
                    icon: 'success',
                    title: 'Request Declined',
                    text: 'You have declined this adviser request.',
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
        },
    );
};
</script>

<template>
    <Head title="Adviser Requests" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-4 md:px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem>Adviser Requests</CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <main class="space-y-5 p-4 md:p-6">
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-5 shadow-sm md:px-6">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 md:text-2xl">Adviser Requests</h1>

                        <p class="mt-1 text-sm text-gray-500">Only proposals shared to you by the focal person will appear here.</p>
                    </div>
                </div>

                <div v-if="!projects.length" class="flex min-h-[55vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FolderOpen />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Shared Proposals</EmptyTitle>

                        <EmptyDescription>
                            Shared student topic proposals will appear here once the focal person sends them to you.
                        </EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>

                <div v-else class="overflow-hidden rounded-xl border border-gray-200">
                    <div
                        class="hidden border-b border-gray-100 px-5 py-3 text-xs font-bold uppercase tracking-wide text-gray-500 md:grid md:grid-cols-[1.3fr_1.5fr_1.2fr_150px] md:items-center md:gap-4"
                    >
                        <div class="pl-3">Student Leader</div>
                        <div class="pl-2">Department</div>
                        <div class="pl-1">Project Type</div>
                        <div class="text-center">Action</div>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="project in projects"
                            :key="project.id"
                            class="grid gap-4 px-5 py-6 md:grid-cols-[1.3fr_1.5fr_1.2fr_150px] md:items-center md:gap-4"
                        >
                            <div class="min-w-0 pl-3">
                                <span class="mb-1 block text-sm font-medium uppercase tracking-wide text-gray-500 md:hidden"> Student Leader </span>

                                <p class="truncate text-sm font-medium text-gray-900">
                                    {{ project.student?.name ?? 'No student assigned' }}
                                </p>
                            </div>

                            <div class="min-w-0 pl-2 text-sm font-medium text-gray-700">
                                <span class="mb-1 block text-sm font-medium uppercase tracking-wide text-gray-500 md:hidden"> Department </span>

                                <p class="truncate">
                                    {{ project.student?.department?.name ?? 'No department' }}
                                </p>
                            </div>

                            <div class="min-w-0 pl-1 text-sm font-medium text-gray-700">
                                <span class="mb-1 block text-sm font-medium uppercase tracking-wide text-gray-500 md:hidden"> Project Type </span>

                                <p class="truncate">
                                    {{ project.project_type ?? 'N/A' }}
                                </p>
                            </div>

                            <div class="flex justify-start md:justify-center">
                                <button
                                    type="button"
                                    @click="openReview(project)"
                                    class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 transition hover:text-[#0C4B05] hover:underline"
                                >
                                    Review
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <div
                v-if="selectedProject"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/45 px-3 py-4 backdrop-blur-[2px] sm:items-center sm:p-6"
                @click.self="closeReview"
            >
                <div class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="flex max-h-[90vh] flex-col">
                        <div class="border-b border-gray-100 px-5 py-5 sm:px-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <h2 class="mt-1 text-lg font-bold text-gray-900 sm:text-xl">Review Shared Topic Proposals</h2>

                                    <p class="mt-1 text-sm text-gray-500">
                                        Select and vote for the topic you want before accepting this adviser request.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    @click="closeReview"
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-800"
                                >
                                    <X class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                            <div class="space-y-5">
                                <div class="rounded-xl border border-gray-200 p-4">
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
                                            {{ selectedProject.status ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-if="selectedProject.adviser_status === 'accepted'"
                                        class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-[#0C4B05]"
                                    >
                                        You accepted this project
                                    </span>

                                    <span
                                        v-else-if="selectedProject.adviser_status === 'declined'"
                                        class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-600"
                                    >
                                        You declined this project
                                    </span>

                                    <span v-else class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                        Pending your vote
                                    </span>
                                </div>

                                <div
                                    v-if="isAcceptedByAnotherAdviser(selectedProject)"
                                    class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                                >
                                    This project already has an accepted adviser:
                                    <strong>{{ selectedProject.accepted_adviser_name ?? 'Another adviser' }}</strong
                                    >.
                                </div>

                                <div class="rounded-2xl border border-gray-200 bg-white">
                                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-4 sm:px-5">
                                        <div>
                                            <h3 class="text-base font-bold text-gray-900">Submitted Topics</h3>

                                            <p class="mt-1 text-xs text-gray-500">Vote by selecting one topic, then click Accept Adviser.</p>
                                        </div>
                                    </div>

                                    <div class="p-4 sm:p-5">
                                        <div v-if="selectedProject.proposal_titles?.length" class="space-y-3">
                                            <label
                                                v-for="(title, index) in selectedProject.proposal_titles"
                                                :key="index"
                                                :class="[
                                                    'flex gap-3 rounded-xl border p-4 transition',
                                                    selectedProposalIndex === index ? 'border-[#0C4B05]' : 'border-gray-200',
                                                    selectedProject.adviser_status === 'accepted' || isAcceptedByAnotherAdviser(selectedProject)
                                                        ? 'cursor-default'
                                                        : 'cursor-pointer',
                                                ]"
                                            >
                                                <input
                                                    v-model="selectedProposalIndex"
                                                    type="radio"
                                                    name="selected_proposal"
                                                    :value="index"
                                                    :disabled="
                                                        selectedProject.adviser_status === 'accepted' || isAcceptedByAnotherAdviser(selectedProject)
                                                    "
                                                    class="mt-1 h-4 w-4 border-gray-300 text-[#0C4B05] focus:ring-[#0C4B05]"
                                                />

                                                <div class="min-w-0 flex-1">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                            Topic {{ index + 1 }}
                                                        </p>

                                                        <span
                                                            v-if="selectedProposalIndex === index"
                                                            class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-semibold text-[#0C4B05]"
                                                        >
                                                            <CheckCircle2 class="h-3 w-3" />
                                                            Selected
                                                        </span>
                                                    </div>

                                                    <p class="mt-1 text-sm font-semibold leading-6 text-gray-900">
                                                        {{ title }}
                                                    </p>

                                                    <div class="mt-3">
                                                        <a
                                                            v-if="getTopicFileUrl(selectedProject, index)"
                                                            :href="getTopicFileUrl(selectedProject, index)!"
                                                            target="_blank"
                                                            class="text-sm font-medium text-[#0C4B05] underline underline-offset-2 hover:text-[#083604]"
                                                        >
                                                            {{ getTopicFileName(selectedProject, index) }}
                                                        </a>

                                                        <span v-else class="text-sm text-gray-400"> No uploaded file </span>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>

                                        <p v-else class="rounded-xl border border-dashed border-gray-300 p-5 text-center text-sm text-gray-500">
                                            No proposal titles submitted.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 px-5 py-4 sm:px-6">
                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <template v-if="selectedProject.adviser_status === 'accepted' || isAcceptedByAnotherAdviser(selectedProject)">
                                    <button
                                        type="button"
                                        disabled
                                        class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-500 shadow-none"
                                    >
                                        <UserCheck class="h-4 w-4" />
                                        <span>Project Accepted</span>
                                    </button>
                                </template>

                                <template v-else>
                                    <button
                                        type="button"
                                        @click="declineRequest(selectedProject)"
                                        :class="[
                                            'inline-flex items-center justify-center gap-2 rounded-lg border px-5 py-2.5 text-sm font-semibold transition',
                                            selectedProject.adviser_status === 'declined'
                                                ? 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400'
                                                : 'border-red-200 bg-white text-red-600 hover:bg-red-50',
                                        ]"
                                        :disabled="selectedProject.adviser_status === 'declined'"
                                    >
                                        <XCircle class="h-4 w-4" />

                                        <span v-if="selectedProject.adviser_status === 'declined'"> Request Declined </span>

                                        <span v-else>Decline</span>
                                    </button>

                                    <button
                                        type="button"
                                        :disabled="!canAcceptProject(selectedProject)"
                                        @click="acceptRequest(selectedProject)"
                                        :class="[
                                            'inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm transition',
                                            canAcceptProject(selectedProject)
                                                ? 'bg-[#0C4B05] text-white hover:bg-[#0a3d04]'
                                                : 'cursor-not-allowed bg-gray-200 text-gray-500 shadow-none',
                                        ]"
                                    >
                                        <Check class="h-4 w-4" />

                                        <span>
                                            {{ selectedProposalIndex === null ? 'Select Topic' : 'Accept' }}
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>
