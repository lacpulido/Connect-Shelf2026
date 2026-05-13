<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { useAlerts } from '@/composables/useAlerts';
import { Head, router } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Manuscript = {
    id: number;
    title: string;
    filename: string;
    original_filename?: string | null;
    abstract?: string | null;
    project_title: string | null;
    adviser: string | null;
    panelists: string[];
    researchers: string[];
    created_at: string;
    status?: string | null;
    revision_comment?: string | null;
};

const props = defineProps<{
    manuscripts: Manuscript[];
}>();

const { showSuccessAlert, showErrorAlert } = useAlerts();

const processingId = ref<number | null>(null);
const showDetailsModal = ref(false);
const showReviewModal = ref(false);
const selectedPaper = ref<Manuscript | null>(null);

const decision = ref<'approve' | 'revise'>('approve');
const revisionComment = ref('');

const commentError = computed(() => {
    return decision.value === 'revise' && revisionComment.value.trim() === '';
});

function shortTitle(title: string, limit = 55) {
    if (!title) return '';

    const cleanTitle = title.trim().replace(/\s+/g, ' ');

    const capitalized = cleanTitle
        .toLowerCase()
        .split(' ')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

    return capitalized.length > limit ? `${capitalized.slice(0, limit).trim()}...` : capitalized;
}

function normalizeStatus(status?: string | null) {
    return String(status ?? '')
        .toLowerCase()
        .trim();
}

function statusLabel(status?: string | null) {
    const normalized = normalizeStatus(status);

    if (['approved', 'approve', 'accepted', 'accept'].includes(normalized)) {
        return 'Approved';
    }

    if (['revision', 'request_revision', 'requested_revision', 'revise'].includes(normalized)) {
        return 'Request for Revision';
    }

    if (normalized === 'resubmitted') {
        return 'Resubmitted';
    }

    if (['pending', 'submitted', 'for_review'].includes(normalized)) {
        return 'For Review';
    }

    return 'N/A';
}

function isApproved(paper: Manuscript): boolean {
    return ['approved', 'approve', 'accepted', 'accept'].includes(normalizeStatus(paper.status));
}

function isRevision(paper: Manuscript): boolean {
    return ['revision', 'request_revision', 'requested_revision', 'revise'].includes(normalizeStatus(paper.status));
}

function isReviewable(paper: Manuscript): boolean {
    const status = normalizeStatus(paper.status);

    if (isApproved(paper)) return false;
    if (isRevision(paper)) return false;

    return ['pending', 'submitted', 'resubmitted', 'for_review'].includes(status);
}

function manuscriptViewUrl(paper: Manuscript) {
    return route('departmentchair.manuscript.view', { id: paper.id });
}

function openDetailsModal(paper: Manuscript) {
    selectedPaper.value = paper;
    showDetailsModal.value = true;
    document.body.style.overflow = 'hidden';
}

function closeDetailsModal() {
    showDetailsModal.value = false;
    selectedPaper.value = null;
    document.body.style.overflow = '';
}

function openReviewModal(paper: Manuscript) {
    if (!isReviewable(paper)) return;

    selectedPaper.value = paper;
    decision.value = 'approve';
    revisionComment.value = '';
    showReviewModal.value = true;
    document.body.style.overflow = 'hidden';
}

function closeReviewModal() {
    showReviewModal.value = false;
    selectedPaper.value = null;
    decision.value = 'approve';
    revisionComment.value = '';
    document.body.style.overflow = '';
}

function submitReview() {
    if (!selectedPaper.value) return;

    if (commentError.value) {
        showErrorAlert('Comment Required', 'Please add a comment when requesting revision.');
        return;
    }

    const id = selectedPaper.value.id;
    const selectedDecision = decision.value;

    processingId.value = id;

    router.post(
        route('departmentchair.manuscript.review', { id }),
        {
            decision: selectedDecision,
            revision_comment: revisionComment.value.trim(),
        },
        {
            preserveScroll: true,
            preserveState: false,

            onSuccess: async () => {
                await showSuccessAlert(
                    selectedDecision === 'approve' ? 'Approved' : 'Revision Requested',
                    selectedDecision === 'approve'
                        ? 'The manuscript has been approved successfully.'
                        : 'The manuscript has been requested for revision.',
                );

                closeReviewModal();

                router.reload({
                    only: ['manuscripts'],
                    preserveScroll: true,
                    preserveState: false,
                });
            },

            onError: async () => {
                await showErrorAlert('Error', 'Something went wrong while reviewing the manuscript.');
            },

            onFinish: () => {
                processingId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Research Archives" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>Research Archives</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
                    <h1 class="text-2xl font-bold text-gray-900">Manuscripts</h1>
                    <p class="mt-1 text-sm text-gray-500">List of submitted and resubmitted research manuscripts.</p>
                </div>

                <div v-if="props.manuscripts.length === 0" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileText />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Manuscripts Found</EmptyTitle>
                        <EmptyDescription>Once manuscripts are submitted, they will appear here.</EmptyDescription>
                        <EmptyContent />
                    </Empty>
                </div>

                <div v-else class="grid grid-cols-1 items-stretch gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="paper in props.manuscripts"
                        :key="paper.id"
                        class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div class="border-b border-gray-100 bg-gradient-to-r from-[#0C4B05] to-[#146b0c] px-5 py-4">
                            <p class="mb-1 text-xs font-semibold tracking-wider text-white/80">Research Manuscript</p>

                            <h2 class="line-clamp-2 min-h-[3.5rem] text-lg font-bold leading-snug text-white">
                                {{ shortTitle(paper.title) }}
                            </h2>
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="shrink-0 text-sm text-gray-500">Researchers</span>
                                </div>

                                <div class="flex items-start justify-between gap-3">
                                    <span class="shrink-0 text-sm font-medium text-gray-500">Adviser</span>

                                    <div class="max-w-[60%] text-right text-sm font-semibold text-gray-900">
                                        <p>{{ paper.adviser ?? 'N/A' }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start justify-between gap-3">
                                    <span class="shrink-0 text-sm font-medium text-gray-500">Status</span>

                                    <div class="max-w-[60%] text-right text-sm font-semibold text-gray-900">
                                        <p class="text-xs font-normal text-gray-500">
                                            {{ statusLabel(paper.status) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="isRevision(paper) && paper.revision_comment" class="pt-2">
                                    <p
                                        class="line-clamp-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-600"
                                    >
                                        {{ paper.revision_comment }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-auto grid grid-cols-1 gap-3 pt-8" :class="isReviewable(paper) ? 'sm:grid-cols-2' : ''">
                                <button
                                    type="button"
                                    class="flex h-11 w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                                    @click="openDetailsModal(paper)"
                                >
                                    View
                                </button>

                                <button
                                    v-if="isReviewable(paper)"
                                    type="button"
                                    class="flex h-11 w-full items-center justify-center rounded-lg bg-[#0C4B05] px-4 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-70"
                                    :disabled="processingId === paper.id"
                                    @click="openReviewModal(paper)"
                                >
                                    <span v-if="processingId === paper.id">Processing...</span>
                                    <span v-else>Review</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SidebarInset>

        <teleport to="body">
            <div
                v-if="showDetailsModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm"
                @click.self="closeDetailsModal"
            >
                <div class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                        <h2 class="text-lg font-bold text-[#0C4B05]">Manuscript</h2>

                        <button
                            type="button"
                            @click="closeDetailsModal"
                            class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                        >
                            ✕
                        </button>
                    </div>

                    <template v-if="selectedPaper">
                        <div class="min-h-0 flex-1 bg-gray-100 p-3">
                            <div class="h-full overflow-hidden rounded-xl border border-gray-200 bg-white">
                                <iframe :src="manuscriptViewUrl(selectedPaper)" class="h-[70vh] w-full" frameborder="0"></iframe>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </teleport>

        <teleport to="body">
            <div
                v-if="showReviewModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-6 backdrop-blur-sm"
                @click.self="closeReviewModal"
            >
                <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5">
                        <h2 class="text-lg font-bold text-gray-900">Review Manuscript</h2>

                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                            @click="closeReviewModal"
                        >
                            ✕
                        </button>
                    </div>

                    <div v-if="selectedPaper" class="space-y-5 px-6 py-5">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Manuscript Title</p>
                            <p class="mt-1 text-sm font-semibold leading-6 text-gray-900">
                                {{ selectedPaper.title }}
                            </p>

                            <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Current Status</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">
                                {{ statusLabel(selectedPaper.status) }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-800"> Decision </label>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <button
                                    type="button"
                                    class="flex items-center justify-center rounded-xl border px-4 py-3 text-sm font-semibold transition"
                                    :class="
                                        decision === 'approve'
                                            ? 'border-[#0C4B05] bg-[#0C4B05]/10 text-[#0C4B05] ring-2 ring-[#0C4B05]/15'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-[#0C4B05]/40 hover:bg-gray-50'
                                    "
                                    @click="decision = 'approve'"
                                >
                                    Approve Manuscript
                                </button>

                                <button
                                    type="button"
                                    class="flex items-center justify-center rounded-xl border px-4 py-3 text-sm font-semibold transition"
                                    :class="
                                        decision === 'revise'
                                            ? 'border-yellow-500 bg-yellow-50 text-yellow-700 ring-2 ring-yellow-500/15'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-yellow-400 hover:bg-gray-50'
                                    "
                                    @click="decision = 'revise'"
                                >
                                    Request Revision
                                </button>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="block text-sm font-semibold text-gray-800">
                                    Comments
                                    <span v-if="decision === 'revise'" class="text-red-500">*</span>
                                </label>

                                <span class="text-xs text-gray-400"> Required for revision </span>
                            </div>

                            <textarea
                                v-model="revisionComment"
                                rows="5"
                                class="w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#0C4B05] focus:ring-2 focus:ring-[#0C4B05]/20"
                                placeholder="Write your comments here..."
                            ></textarea>

                            <p v-if="commentError" class="mt-2 text-xs font-medium text-red-600">Comments are required when requesting revision.</p>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="inline-flex h-11 items-center justify-center rounded-xl bg-[#0C4B05] px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-[#083804] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="processingId === selectedPaper?.id || commentError"
                            @click="submitReview"
                        >
                            <span v-if="processingId === selectedPaper?.id">Submitting...</span>
                            <span v-else>Submit Review</span>
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </SidebarProvider>
</template>
