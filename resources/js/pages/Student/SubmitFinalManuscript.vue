<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { useAlerts } from '@/composables/useAlerts'
import { Project } from '@/types/project'
import type { PageProps as InertiaPageProps } from '@inertiajs/core'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { CheckCircle2, Clock3, FileText, LoaderCircle, LockKeyhole, UploadCloud } from 'lucide-vue-next'
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
    canSubmitFinalManuscript: boolean
}>()

const page = usePage<PageProps>()
const flashSuccess = computed(() => page.props.flash?.success || '')

const { showSuccessAlert, showErrorAlert } = useAlerts()

// ✅ FILE LIMIT
const MAX_FILE_SIZE = 10 * 1024 * 1024 // 10MB

const form = useForm({
    title: props.project?.title ?? '',
    abstract: '',
    manuscript: null as File | null,
})

const abstractError = ref('')
const fileError = ref('')
const isSaving = ref(false)

// sync title
watch(
    () => props.project,
    (val) => {
        form.title = val?.title ?? ''
    },
    { immediate: true }
)

// success alert
watch(flashSuccess, (val) => {
    if (val) showSuccessAlert('Submitted Successfully', val)
})

// ✅ FILE HANDLER
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

// clean filename
function cleanFileName(name: string | null) {
    if (!name) return ''
    return name.replace(/^\d+_/, '')
}

// submit
function submit() {
    abstractError.value = ''
    fileError.value = ''

    if (!props.canSubmitFinalManuscript) {
        showErrorAlert('Not Allowed', 'Your manuscript must be approved first.')
        return
    }

    let hasError = false

    if (!form.abstract.trim()) {
        abstractError.value = 'Abstract is required.'
        hasError = true
    }

    if (!form.manuscript) {
        fileError.value = 'Please upload a manuscript file.'
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
            showSuccessAlert('Success', 'Final manuscript submitted.')
        },
        onError: (errors) => {
            isSaving.value = false

            if (errors.manuscript) fileError.value = String(errors.manuscript)
            if (errors.abstract) abstractError.value = String(errors.abstract)
        },
    })
}
</script>

<template>
    <Head title="Submit Manuscript" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <!-- HEADER -->
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

                <!-- TITLE -->
                <div class="rounded-2xl border bg-white p-6 shadow-sm">
                    <h1 class="text-2xl font-bold">Final Manuscript</h1>
                    <p class="text-sm text-gray-500">
                        Submit your final manuscript.
                    </p>
                </div>

                <!-- EMPTY STATES -->
                <div v-if="!project" class="flex justify-center">
                    <Empty class="max-w-md border bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon"><FileText /></EmptyMedia>
                        </EmptyHeader>
                        <EmptyTitle>No Project Found</EmptyTitle>
                        <EmptyDescription>No project available.</EmptyDescription>
                    </Empty>
                </div>

                <div v-else-if="manuscriptStatus === 'approved'" class="flex justify-center">
                    <Empty class="max-w-md border bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon"><CheckCircle2 /></EmptyMedia>
                        </EmptyHeader>
                        <EmptyTitle>Approved</EmptyTitle>
                        <EmptyDescription>Your manuscript is approved.</EmptyDescription>
                    </Empty>
                </div>

                <div v-else-if="manuscriptSubmitted" class="flex justify-center">
                    <Empty class="max-w-md border bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon"><Clock3 /></EmptyMedia>
                        </EmptyHeader>
                        <EmptyTitle>Pending</EmptyTitle>
                        <EmptyDescription>Under review.</EmptyDescription>
                    </Empty>
                </div>

                <div v-else-if="!canSubmitFinalManuscript" class="flex justify-center">
                    <Empty class="max-w-md border bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon"><LockKeyhole /></EmptyMedia>
                        </EmptyHeader>
                        <EmptyTitle>Not Allowed</EmptyTitle>
                        <EmptyDescription>Adviser approval required.</EmptyDescription>
                    </Empty>
                </div>

                <!-- FORM -->
                <div v-else class="rounded-2xl border bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">

                        <!-- TITLE -->
                        <div>
                            <p class="text-xs text-gray-500">Title</p>
                            <p class="border-b py-2 font-medium">
                                {{ form.title }}
                            </p>
                        </div>

                        <!-- ABSTRACT -->
                        <div>
                            <label class="text-sm font-medium">Abstract</label>

                            <textarea
                                v-model="form.abstract"
                                rows="8"
                                class="mt-2 w-full rounded-xl border px-4 py-3 focus:border-[#0C4B05]"
                            />

                            <p v-if="abstractError" class="text-xs text-red-500 mt-1">
                                {{ abstractError }}
                            </p>
                        </div>

                        <!-- FILE UPLOAD -->
                        <div>
                            <label class="text-sm font-medium">Upload Manuscript</label>

                            <label
                                class="mt-2 flex min-h-[160px] flex-col items-center justify-center rounded-2xl border border-dashed bg-gray-50 cursor-pointer transition"
                                :class="fileError ? 'border-red-400 bg-red-50' : 'hover:border-[#0C4B05]'"
                            >
                                <UploadCloud
                                    class="h-10 w-10 mb-2"
                                    :class="fileError ? 'text-red-500' : 'text-[#0C4B05]'"
                                />

                                <span class="text-sm">Click to upload</span>
                                <span class="text-xs text-gray-500">PDF • Max 10MB</span>

                                <input type="file" accept=".pdf" @change="handleFileChange" class="hidden" />
                            </label>

                            <!-- selected file -->
                            <p v-if="form.manuscript" class="mt-2 text-sm">
                                Selected: <span class="font-medium">{{ cleanFileName(form.manuscript.name) }}</span>
                            </p>

                            <!-- ✅ IN-PAGE ERROR -->
                            <p v-if="fileError" class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-600">
                                {{ fileError }}
                            </p>
                        </div>

                        <!-- SUBMIT -->
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="isSaving"
                                class="flex items-center gap-2 rounded-full bg-[#0C4B05] px-8 py-3 text-white"
                            >
                                <LoaderCircle v-if="isSaving" class="animate-spin h-4 w-4" />
                                {{ isSaving ? 'Uploading...' : 'Submit' }}
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </SidebarInset>
    </SidebarProvider>
</template>