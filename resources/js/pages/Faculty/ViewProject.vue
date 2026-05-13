<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { useAlerts } from '@/composables/useAlerts'
import { useDateFormatter } from '@/composables/useDateFormatter'
import { useGroupedSections } from '@/composables/useGroupedSections'
import { useLatestDocuments } from '@/composables/useLatestDocuments'
import { useSubmissionAccordion } from '@/composables/useSubmissionsAccordion'
import { useSubmissionStatus } from '@/composables/useSubmissionStatus'
import { SECTION_DEFINITIONS } from '@/constants/submissionSections'
import type { Project, Submission } from '@/types'
import { parseSubmissionSlug } from '@/utils/parseSubmissionSlug'
import { Head, Link, router, usePage, usePoll } from '@inertiajs/vue3'
import { ChevronDown, ChevronUp, FileText, SquarePen, X } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'

type PageProps = {
    project?: Project | null
    documents?: Submission[][]
    flash?: {
        success?: string
        error?: string
    }
}

const page = usePage<PageProps>()

const project = computed<Project | null>(() => page.props.project ?? null)
const documents = computed<Submission[][]>(() => page.props.documents ?? [])

const { formatDateTime } = useDateFormatter()
const { statusClasses, statusLabel } = useSubmissionStatus()
const { showSuccessAlert, showErrorAlert } = useAlerts()

const latestDocuments = useLatestDocuments(documents)
const { groupedSections } = useGroupedSections(latestDocuments, SECTION_DEFINITIONS)

const { openSections, toggleSection, isOpen } = useSubmissionAccordion(
    SECTION_DEFINITIONS,
    computed(() => page.url),
    documents,
)

const activeReview = ref<number | null>(null)
const reviewComment = ref('')
const reviewDecision = ref<'approved' | 'needs_revision'>('needs_revision')
const submitting = ref(false)

const selectedFileUrl = ref<string | null>(null)
const selectedFileName = ref('')
const isFileViewerOpen = ref(false)

const hasProject = computed(() => !!project.value)

const resetReviewForm = () => {
    reviewComment.value = ''
    reviewDecision.value = 'needs_revision'
}

const toggleReview = (id: number) => {
    if (activeReview.value === id) {
        activeReview.value = null
        resetReviewForm()
        return
    }

    activeReview.value = id
    resetReviewForm()
}

const resolveDocumentUrl = (version: Submission) => {
    if (version?.slug) {
        return route('faculty.documents.view', {
            document: version.slug,
        })
    }

    return version?.file_url ?? '#'
}

const openFileViewer = (version: Submission) => {
    selectedFileUrl.value = resolveDocumentUrl(version)
    selectedFileName.value = version.filename ?? 'Document Preview'
    isFileViewerOpen.value = true
}

const closeFileViewer = () => {
    isFileViewerOpen.value = false
    selectedFileUrl.value = null
    selectedFileName.value = ''
}

const submitReview = (documentSlug?: string) => {
    if (!documentSlug || submitting.value) return

    submitting.value = true

    router.post(
        route('faculty.documents.comment.store', { document: documentSlug }),
        {
            comment: reviewComment.value,
            decision: reviewDecision.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                const decision = reviewDecision.value

                resetReviewForm()
                activeReview.value = null

                showSuccessAlert(
                    'Review Submitted',
                    decision === 'approved'
                        ? 'Document approved successfully.'
                        : 'Document requires revision.',
                )

                router.reload({
                    only: ['documents', 'project'],
                })
            },
            onError: () => {
                showErrorAlert('Error', 'Something went wrong.')
            },
            onFinish: () => {
                submitting.value = false
            },
        },
    )
}

onMounted(() => {
    const firstKey = SECTION_DEFINITIONS[0]?.key
    const hasOpenQuery = new URLSearchParams(window.location.search).get('open')

    if (firstKey && !hasOpenQuery) {
        openSections.value[firstKey] = true
    }

    if (page.props.flash?.success) {
        showSuccessAlert('Success!', page.props.flash.success)
    }

    if (page.props.flash?.error) {
        showErrorAlert('Error', page.props.flash.error)
    }
})

usePoll(2000, {
    only: ['documents', 'project'],
})
</script>

<template>
    <Head title="Project Submissions" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>Submissions</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <div class="rounded-2xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
                    <h1 class="text-xl font-semibold text-gray-900">
                        {{ project?.title ?? 'Project Submissions' }}
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Review submitted project documents and provide feedback.
                    </p>

                    <button
                        v-if="project"
                        type="button"
                        @click="router.visit(route('faculty.projects'))"
                        class="mt-3 inline-flex items-center text-sm font-medium text-gray-600 transition hover:text-[#0C4B05]"
                    >
                        ← Back to All Projects
                    </button>
                </div>

                <div
                    v-if="!hasProject"
                    class="rounded-2xl border border-yellow-200 bg-yellow-50 px-6 py-6 text-center shadow-sm"
                >
                    <p class="text-sm text-yellow-700">
                        No project found for this submission page.
                    </p>
                </div>

                <div v-if="hasProject" class="space-y-4">
                    <div
                        v-for="section in groupedSections"
                        :key="section.key"
                        class="rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm"
                    >
                        <button
                            type="button"
                            @click="toggleSection(section.key)"
                            class="flex w-full items-center justify-between text-left"
                        >
                            <h2 class="text-lg font-semibold tracking-wide text-gray-900">
                                {{ section.label }}
                            </h2>

                            <span class="flex items-center">
                                <ChevronUp
                                    v-if="isOpen(section.key)"
                                    class="h-4 w-4 text-gray-700"
                                />
                                <ChevronDown
                                    v-else
                                    class="h-4 w-4 text-gray-700"
                                />
                            </span>
                        </button>

                        <div v-if="isOpen(section.key)" class="mt-5">
                            <div v-if="section.documents.length > 0" class="space-y-5">
                                <div
                                    v-for="(version, index) in section.documents"
                                    :key="version.id"
                                >
                                    <div class="rounded-2xl border border-gray-200 bg-white p-5 transition hover:shadow-sm">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900">
                                                    Version {{ index + 1 }}
                                                </span>

                                                <span
                                                    v-if="version.status"
                                                    :class="[
                                                        'rounded-full px-3 py-1 text-xs font-medium capitalize',
                                                        statusClasses(version.status),
                                                    ]"
                                                >
                                                    {{ statusLabel(version.status) }}
                                                </span>
                                            </div>

                                            <span class="text-xs text-gray-400">
                                                {{ formatDateTime(version.created_at) }}
                                            </span>
                                        </div>

                                        <div class="mt-4 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                            <div class="flex items-start gap-3">
                                                <div class="mt-0.5 rounded-xl bg-white p-2 shadow-sm">
                                                    <FileText class="h-5 w-5 text-[#0C4B05]" />
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <button
                                                        type="button"
                                                        @click="openFileViewer(version)"
                                                        class="block break-words text-left text-sm font-semibold text-gray-900 hover:text-[#0C4B05] hover:underline"
                                                    >
                                                        {{ version.filename }}
                                                    </button>

                                                    <p class="mt-1 text-xs text-gray-500">
                                                        Uploaded on {{ formatDateTime(version.created_at) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 border-t border-gray-200 pt-3">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                                Reviewer Feedback
                                            </p>

                                            <div
                                                v-if="version.comments?.length && version.comments[0]?.comment?.trim()"
                                                class="mt-2"
                                            >
                                                <p class="text-sm leading-relaxed text-gray-700">
                                                    {{ version.comments[0].comment }}
                                                </p>
                                            </div>

                                            <div v-else class="mt-2">
                                                <p class="text-sm italic text-gray-400">
                                                    No feedback provided yet.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap justify-end gap-2 pt-4">
                                            <button
                                                v-if="!version.comments?.length"
                                                type="button"
                                                @click="toggleReview(version.id)"
                                                class="inline-flex min-w-[140px] items-center justify-center gap-2 rounded-md bg-[#0C4B05] px-4 py-2.5 text-sm font-medium text-white transition hover:opacity-90"
                                            >
                                                <SquarePen class="h-4 w-4" />
                                                {{ activeReview === version.id ? 'Cancel Review' : 'Add Review' }}
                                            </button>

                                            <Link
                                                v-if="project && version.slug"
                                                :href="(() => {
                                                    const { folder, document } = parseSubmissionSlug(version.slug)

                                                    return route('faculty.submission.show', {
                                                        project: project.slug,
                                                        folder,
                                                        document,
                                                        open: section.key,
                                                    })
                                                })()"
                                                class="inline-flex min-w-[140px] items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                                            >
                                                View History
                                            </Link>
                                        </div>

                                        <div v-if="activeReview === version.id" class="mt-4">
                                            <textarea
                                                v-model="reviewComment"
                                                rows="4"
                                                class="w-full rounded-xl border border-gray-300 p-3 text-sm outline-none transition focus:border-gray-400"
                                                placeholder="Enter your feedback or suggestions..."
                                            />

                                            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                <select
                                                    v-model="reviewDecision"
                                                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 outline-none"
                                                >
                                                    <option value="needs_revision">Request Revision</option>
                                                    <option value="approved">Approve Submission</option>
                                                </select>

                                                <button
                                                    type="button"
                                                    :disabled="submitting"
                                                    @click="submitReview(version.slug)"
                                                    class="inline-flex min-w-[140px] items-center justify-center rounded-md bg-[#0C4B05] px-4 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    {{ submitting ? 'Submitting...' : 'Submit Review' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center"
                            >
                                <p class="text-sm text-gray-500">
                                    No submissions available for this section.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="isFileViewerOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            >
                <div class="flex h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b px-5 py-3">
                        <h2 class="truncate text-sm font-semibold text-gray-900">
                            {{ selectedFileName }}
                        </h2>

                        <button
                            type="button"
                            @click="closeFileViewer"
                            class="rounded-full p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <iframe
                        v-if="selectedFileUrl"
                        :src="selectedFileUrl"
                        class="h-full w-full"
                    ></iframe>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>