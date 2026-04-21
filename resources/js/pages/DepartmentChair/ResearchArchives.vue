<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { useAlerts } from '@/composables/useAlerts'
import { useDateFormatter } from '@/composables/useDateFormatter'
import { Head, router } from '@inertiajs/vue3'
import { FileText } from 'lucide-vue-next'
import { ref } from 'vue'

type Manuscript = {
    id: number
    title: string
    filename: string
    original_filename?: string | null
    abstract: string
    status: string
    project_title: string | null
    adviser: string | null
    researchers: string[]
    created_at: string
}

const props = defineProps<{
    manuscripts: Manuscript[]
}>()

const approvingId = ref<number | null>(null)
const successMessage = ref<string | null>(null)
const showModal = ref(false)
const selectedPaper = ref<Manuscript | null>(null)

const { formatDateTime } = useDateFormatter()
const { confirmAction, showSuccessAlert, showErrorAlert } = useAlerts()

function shortTitle(title: string, limit = 55) {
    if (!title) return ''

    const cleanTitle = title.trim().replace(/\s+/g, ' ')

    const capitalized = cleanTitle
        .toLowerCase()
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')

    return capitalized.length > limit
        ? `${capitalized.slice(0, limit).trim()}...`
        : capitalized
}

function openModal(paper: Manuscript) {
    selectedPaper.value = paper
    showModal.value = true
    document.body.style.overflow = 'hidden'
}

function closeModal() {
    showModal.value = false
    selectedPaper.value = null
    document.body.style.overflow = ''
}

function getDisplayStatus(status: string) {
    if (!status) return 'Pending'

    return status
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

function getStatusClass(status: string) {
    const normalized = (status || '').toLowerCase()

    if (normalized === 'approved') {
        return 'bg-green-100 text-green-700 border border-green-200'
    }

    if (normalized === 'pending') {
        return 'bg-yellow-100 text-yellow-700 border border-yellow-200'
    }

    if (normalized === 'rejected') {
        return 'bg-red-100 text-red-700 border border-red-200'
    }

    return 'bg-gray-100 text-gray-700 border border-gray-200'
}

function getAbstractParagraphs(abstractText: string | null | undefined): string[] {
    if (!abstractText || !abstractText.trim()) {
        return ['No abstract available.']
    }

    return abstractText
        .split(/\n\s*\n/)
        .map((paragraph) => paragraph.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim())
        .filter((paragraph) => paragraph.length > 0)
}

async function approve(id: number) {
    const result = await confirmAction(
        'Are you sure you want to accept?',
        'This action will approve the manuscript.',
        'Yes, accept it',
        'Cancel',
    )

    if (!result.isConfirmed) return

    approvingId.value = id

    router.post(
        route('departmentchair.manuscript.approve', { id }),
        {},
        {
            preserveScroll: true,
            onSuccess: async () => {
                successMessage.value = 'Manuscript approved successfully'
                await showSuccessAlert('Approved', 'The manuscript has been approved successfully.')
            },
            onError: async () => {
                await showErrorAlert('Error', 'Something went wrong while approving the manuscript.')
            },
            onFinish: () => {
                approvingId.value = null

                setTimeout(() => {
                    successMessage.value = null
                }, 3000)
            },
        },
    )
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
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Manuscripts</h1>
                            <p class="mt-1 text-sm text-gray-500">
                                List of submitted research manuscripts.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="successMessage"
                    class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700"
                >
                    {{ successMessage }}
                </div>

                <div v-if="props.manuscripts.length === 0" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileText />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Manuscripts Found</EmptyTitle>
                        <EmptyDescription>
                            Once manuscripts are submitted, they will appear here.
                        </EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <div
                        v-for="paper in props.manuscripts"
                        :key="paper.id"
                        class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div class="border-b border-gray-100 bg-gradient-to-r from-[#0C4B05] to-[#146b0c] px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="mb-1 text-xs font-semibold tracking-wider text-white/80">
                                        Research Manuscript
                                    </p>
                                    <h2
                                        class="line-clamp-2 min-h-[3.5rem] text-lg font-bold leading-snug text-white"
                                        :title="paper.title"
                                    >
                                        {{ shortTitle(paper.title) }}
                                    </h2>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm font-medium text-gray-500">File</span>
                                    <a
                                        :href="route('departmentchair.manuscript.view', { id: paper.id })"
                                        target="_blank"
                                        class="max-w-[60%] break-words text-right text-sm font-semibold text-[#0C4B05] hover:underline"
                                    >
                                        {{ paper.original_filename ?? paper.filename }}
                                    </a>
                                </div>

                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm font-medium text-gray-500">Adviser</span>
                                    <span class="text-right text-sm font-semibold text-gray-900">
                                        {{ paper.adviser ?? 'N/A' }}
                                    </span>
                                </div>

                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm font-medium text-gray-500">Submitted</span>
                                    <span class="text-right text-sm font-semibold text-gray-900">
                                        {{ formatDateTime(paper.created_at) }}
                                    </span>
                                </div>

                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm font-medium text-gray-500">Status</span>
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="getStatusClass(paper.status)"
                                    >
                                        {{ getDisplayStatus(paper.status) }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="paper.researchers?.length"
                                class="mt-5 flex-1 border-t border-gray-100 pt-4"
                            >
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    Researchers
                                </p>

                                <ul class="mt-3 space-y-1">
                                    <li
                                        v-for="(researcher, index) in paper.researchers"
                                        :key="index"
                                        class="text-sm text-gray-700"
                                    >
                                        <span class="font-semibold text-gray-900">
                                            {{ researcher }}
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <div
                                v-else
                                class="mt-5 flex-1 border-t border-gray-100 pt-4"
                            >
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    Researchers
                                </p>
                                <p class="mt-3 text-sm text-gray-500">
                                    No researchers available.
                                </p>
                            </div>

                            <div class="mt-5 border-t border-gray-100 pt-4">
                                <template v-if="paper.status === 'approved'">
                                    <button
                                        type="button"
                                        class="w-full rounded-md bg-[#0C4B05] px-4 py-2 text-sm text-white hover:opacity-90"
                                        @click="openModal(paper)"
                                    >
                                        View Details
                                    </button>
                                </template>

                                <template v-else>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button
                                            type="button"
                                            class="rounded-md bg-gray-600 px-4 py-2 text-sm text-white hover:opacity-90"
                                            @click="openModal(paper)"
                                        >
                                            View Details
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-md bg-[#0C4B05] px-4 py-2 text-sm text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-70"
                                            :disabled="approvingId === paper.id"
                                            @click="approve(paper.id)"
                                        >
                                            <span v-if="approvingId === paper.id">Processing...</span>
                                            <span v-else>Approve</span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <teleport to="body">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 px-4 pb-6 pt-14 backdrop-blur-sm"
                    @click.self="closeModal"
                >
                    <div
                        class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                    >
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <div>
                                <h2 class="text-xl font-bold text-[#0C4B05]">
                                    Manuscript Details
                                </h2>
                                <p class="mt-1 text-sm text-gray-500">
                                    Review the submitted manuscript information below.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="closeModal"
                                class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                            >
                                ✕
                            </button>
                        </div>

                        <template v-if="selectedPaper">
                            <div class="px-6 py-5">
                                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                                    <div class="mb-4">
                                        <h3 class="text-sm font-semibold text-gray-800">
                                            Manuscript Overview
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600">
                                            Selected manuscript details.
                                        </p>
                                    </div>

                                    <div class="space-y-3 text-sm text-gray-700">
                                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                            <span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Title
                                            </span>
                                            <p class="mt-1 font-medium leading-relaxed text-gray-800">
                                                {{ selectedPaper.title || 'Untitled Manuscript' }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                            <span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Researchers
                                            </span>

                                            <ul
                                                v-if="selectedPaper.researchers?.length"
                                                class="mt-2 list-disc space-y-1 pl-5 text-gray-800"
                                            >
                                                <li
                                                    v-for="(researcher, index) in selectedPaper.researchers"
                                                    :key="index"
                                                >
                                                    {{ researcher }}
                                                </li>
                                            </ul>

                                            <p v-else class="mt-1 font-medium text-gray-500">
                                                No researchers available.
                                            </p>
                                        </div>

                                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                            <span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Abstract
                                            </span>

                                            <div class="mt-2 space-y-3">
                                                <p
                                                    v-for="(paragraph, index) in getAbstractParagraphs(selectedPaper.abstract)"
                                                    :key="index"
                                                    class="leading-7 text-justify text-gray-800"
                                                    style="text-justify: inter-word;"
                                                >
                                                    {{ paragraph }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </teleport>
        </SidebarInset>
    </SidebarProvider>
</template>