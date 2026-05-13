<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { useAlerts } from '@/composables/useAlerts'
import { Project } from '@/types/project'
import type { PageProps as InertiaPageProps } from '@inertiajs/core'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import {
    AlertTriangle,
    CheckCircle2,
    Clock3,
    FileText,
    LoaderCircle,
    LockKeyhole,
    UploadCloud,
} from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

interface PageProps extends InertiaPageProps {
    flash: {
        success?: string
        error?: string
    }
}

const props = defineProps<{
    project: Project | null
    manuscriptSubmitted: boolean
    manuscriptStatus: string | null
    manuscriptFileName: string | null
    revisionComments: string | null
    canSubmitFinalManuscript: boolean
}>()

const page = usePage<PageProps>()
const flashSuccess = computed(() => page.props.flash?.success || '')

const { showSuccessAlert, showErrorAlert } = useAlerts()

const MAX_FILE_SIZE = 10 * 1024 * 1024

const abstractError = ref('')
const fileError = ref('')
const isSaving = ref(false)

const normalizedStatus = computed(() =>
    String(props.manuscriptStatus ?? '').toLowerCase().trim()
)

const isRevision = computed(() =>
    ['revision', 'request_revision', 'requested_revision', 'revise'].includes(normalizedStatus.value)
)

const form = useForm({
    title: props.project?.title ?? '',
    abstract: '',
    manuscript: null as File | null,
})

watch(
    () => props.project,
    (val) => {
        form.title = val?.title ?? ''
    },
    { immediate: true }
)

watch(flashSuccess, (val) => {
    if (val) showSuccessAlert('Submitted Successfully', val)
})

function handleFileChange(event: Event) {
    const input = event.target as HTMLInputElement

    fileError.value = ''
    form.manuscript = null

    if (!input.files?.length) return

    const file = input.files[0]

    if (file.size > MAX_FILE_SIZE) {
        fileError.value = 'File is too large. Maximum size is 10MB.'
        input.value = ''
        return
    }

    if (!file.name.toLowerCase().endsWith('.pdf')) {
        fileError.value = 'Only PDF files are allowed.'
        input.value = ''
        return
    }

    form.manuscript = file
}

function cleanFileName(name: string | null) {
    if (!name) return ''
    return name.replace(/^\d+_/, '')
}

function submit() {
    abstractError.value = ''
    fileError.value = ''

    if (!props.canSubmitFinalManuscript) {
        showErrorAlert('Not Allowed', 'Your manuscript must be approved first.')
        return
    }

    let hasError = false

    if (!isRevision.value && !form.abstract.trim()) {
        abstractError.value = 'Abstract is required.'
        hasError = true
    }

    if (!form.manuscript) {
        fileError.value = isRevision.value
            ? 'Please upload your revised manuscript file.'
            : 'Please upload a manuscript file.'
        hasError = true
    }

    if (form.manuscript && form.manuscript.size > MAX_FILE_SIZE) {
        fileError.value = 'File is too large. Maximum size is 10MB.'
        hasError = true
    }

    if (hasError) return

    isSaving.value = true

    form.post(route('student.submit-final-manuscript.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            form.title = props.project?.title ?? ''
            isSaving.value = false

            showSuccessAlert(
                'Success',
                isRevision.value
                    ? 'Final manuscript resubmitted.'
                    : 'Final manuscript submitted.'
            )
        },
        onError: (errors) => {
            isSaving.value = false

            if (errors.manuscript) fileError.value = String(errors.manuscript)
            if (errors.abstract) abstractError.value = String(errors.abstract)
            if (errors.error) showErrorAlert('Error', String(errors.error))
        },
        onFinish: () => {
            isSaving.value = false
        },
    })
}
</script>

<template>
    <Head title="Submit Manuscript" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-3 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-6" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>Submit Manuscript</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <div class="rounded-2xl border bg-white p-6 shadow-sm">
                    <h1 class="text-2xl font-bold">Final Manuscript</h1>
                    <p class="text-sm text-gray-500">
                        Submit your final manuscript.
                    </p>
                </div>

                <div v-if="!project" class="flex justify-center">
                    <Empty class="max-w-md border bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileText />
                            </EmptyMedia>
                        </EmptyHeader>
                        <EmptyTitle>No Project Found</EmptyTitle>
                        <EmptyDescription>No project available.</EmptyDescription>
                    </Empty>
                </div>

                <div v-else-if="normalizedStatus === 'approved'" class="flex justify-center">
                    <Empty class="max-w-md border bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <CheckCircle2 />
                            </EmptyMedia>
                        </EmptyHeader>
                        <EmptyTitle>Approved</EmptyTitle>
                        <EmptyDescription>Your manuscript is approved.</EmptyDescription>
                    </Empty>
                </div>

                <div
                    v-else-if="manuscriptSubmitted && !isRevision"
                    class="flex justify-center"
                >
                    <Empty class="max-w-md border bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Clock3 />
                            </EmptyMedia>
                        </EmptyHeader>
                        <EmptyTitle>Pending</EmptyTitle>
                        <EmptyDescription>Your final manuscript is under review.</EmptyDescription>
                    </Empty>
                </div>

                <div v-else-if="!canSubmitFinalManuscript" class="flex justify-center">
                    <Empty class="max-w-md border bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <LockKeyhole />
                            </EmptyMedia>
                        </EmptyHeader>
                        <EmptyTitle>Not Allowed</EmptyTitle>
                        <EmptyDescription>Adviser approval required.</EmptyDescription>
                    </Empty>
                </div>

                <div v-else class="rounded-2xl border bg-white p-6 shadow-sm">
                    <div
                        v-if="isRevision"
                        class="mb-6 rounded-xl border border-yellow-300 bg-yellow-50 p-4"
                    >
                        <div class="flex gap-3">
                            <AlertTriangle class="mt-0.5 h-5 w-5 text-yellow-700" />

                            <div class="flex-1">
                                <p class="text-sm font-semibold text-yellow-800">
                                    Revision Required
                                </p>

                                <p class="mt-1 text-sm text-yellow-700">
                                    Your final manuscript needs revision. Please upload your revised manuscript file below.
                                </p>

                                <div class="mt-3 rounded-lg border border-yellow-200 bg-white p-3 text-sm text-gray-700">
                                    {{ revisionComments || 'No revision comments provided.' }}
                                </div>

                                <p v-if="manuscriptFileName" class="mt-3 text-xs text-yellow-700">
                                    Current file:
                                    <span class="font-semibold">
                                        {{ cleanFileName(manuscriptFileName) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <template v-if="!isRevision">
                            <div>
                                <p class="text-xs text-gray-500">Title</p>
                                <p class="border-b py-2 font-medium">
                                    {{ form.title }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium">Abstract</label>

                                <textarea
                                    v-model="form.abstract"
                                    rows="8"
                                    class="mt-2 w-full rounded-xl border px-4 py-3 focus:border-[#0C4B05] focus:outline-none focus:ring-1 focus:ring-[#0C4B05]"
                                />

                                <p v-if="abstractError" class="mt-1 text-xs text-red-500">
                                    {{ abstractError }}
                                </p>
                            </div>
                        </template>

                        <div>
                            <label class="text-sm font-medium">
                                {{ isRevision ? 'Upload Revised Manuscript' : 'Upload Manuscript' }}
                            </label>

                            <label
                                class="mt-2 flex min-h-[160px] cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed bg-gray-50 transition"
                                :class="fileError ? 'border-red-400 bg-red-50' : 'hover:border-[#0C4B05]'"
                            >
                                <UploadCloud
                                    class="mb-2 h-10 w-10"
                                    :class="fileError ? 'text-red-500' : 'text-[#0C4B05]'"
                                />

                                <span class="text-sm">Click to upload</span>
                                <span class="text-xs text-gray-500">PDF • Max 10MB</span>

                                <input
                                    type="file"
                                    accept=".pdf"
                                    class="hidden"
                                    @change="handleFileChange"
                                />
                            </label>

                            <p v-if="form.manuscript" class="mt-2 text-sm">
                                Selected:
                                <span class="font-medium">
                                    {{ cleanFileName(form.manuscript.name) }}
                                </span>
                            </p>

                            <p
                                v-if="fileError"
                                class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-600"
                            >
                                {{ fileError }}
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="isSaving"
                                class="inline-flex items-center justify-center gap-2 rounded-md bg-[#0C4B05] px-6 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <LoaderCircle v-if="isSaving" class="h-4 w-4 animate-spin" />

                                {{
                                    isSaving
                                        ? isRevision
                                            ? 'Resubmitting...'
                                            : 'Uploading...'
                                        : isRevision
                                            ? 'Resubmit Manuscript'
                                            : 'Submit'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>