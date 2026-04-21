<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { CheckCircle2, Clock3, FileText, LoaderCircle } from 'lucide-vue-next'
import type { PageProps as InertiaPageProps } from '@inertiajs/core'
import { Project } from '@/types/project'
import AppSidebar from '@/components/AppSidebar.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty'
import { useAlerts } from '@/composables/useAlerts'

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
}>()

const page = usePage<PageProps>()
const flashSuccess = computed(() => page.props.flash?.success || '')

const { showSuccessAlert, showErrorAlert } = useAlerts()

const form = useForm({
    title: props.project?.title ?? '',
    abstract: '',
    manuscript: null as File | null,
})

const abstractError = ref('')
const fileError = ref('')
const isSaving = ref(false)

watch(
    () => props.project,
    (newProject) => {
        form.title = newProject?.title ?? ''
    },
    { immediate: true },
)

watch(
    flashSuccess,
    (value) => {
        if (value) {
            showSuccessAlert('Submitted Successfully', value)
        }
    },
    { immediate: true },
)

function handleFileChange(event: Event) {
    const input = event.target as HTMLInputElement

    if (input.files?.length) {
        form.manuscript = input.files[0]
        fileError.value = ''
    }
}

function cleanFileName(name: string | null): string {
    if (!name) return ''

    return name.replace(/^\d+_/, '')
}

function submit() {
    abstractError.value = ''
    fileError.value = ''

    let hasError = false

    if (!form.abstract.trim()) {
        abstractError.value = 'Abstract is required.'
        hasError = true
    }

    if (!form.manuscript) {
        fileError.value = 'Please upload a manuscript file.'
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
            showSuccessAlert('Submitted Successfully', 'Final manuscript submitted successfully.')
        },
        onError: (errors) => {
            const firstError =
                errors.error ||
                errors.manuscript ||
                errors.abstract ||
                errors.title ||
                'Something went wrong.'

            isSaving.value = false
            showErrorAlert('Submission Failed', String(firstError))
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
                        <BreadcrumbItem class="font">
                            Submit Manuscript
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <div class="rounded-2xl border border-gray-200 bg-white px-6 py-6 shadow-sm">
                    <h1 class="text-2xl font-bold text-gray-900">
                        Final Manuscript
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Submit and manage your final manuscript for project review.
                    </p>
                </div>

                <div v-if="!project" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileText />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Project Found</EmptyTitle>
                        <EmptyDescription>
                            No project found for your account.
                        </EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>

                <div
                    v-else-if="manuscriptStatus === 'approved'"
                    class="flex min-h-[60vh] items-center justify-center"
                >
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <CheckCircle2 />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>Approved</EmptyTitle>
                        <EmptyDescription>
                            Your final manuscript has been successfully approved.
                        </EmptyDescription>

                        <EmptyContent>
                            <p
                                v-if="manuscriptFileName"
                                class="text-sm text-gray-700"
                            >
                                File: {{ cleanFileName(manuscriptFileName) }}
                            </p>
                        </EmptyContent>
                    </Empty>
                </div>

                <div
                    v-else-if="project.adviser_id && manuscriptSubmitted"
                    class="flex min-h-[60vh] items-center justify-center"
                >
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Clock3 />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>Pending Review</EmptyTitle>
                        <EmptyDescription>
                            Your manuscript is under review.
                        </EmptyDescription>

                        <EmptyContent>
                            <p
                                v-if="manuscriptFileName"
                                class="text-sm text-gray-700"
                            >
                                Uploaded File: {{ cleanFileName(manuscriptFileName) }}
                            </p>
                        </EmptyContent>
                    </Empty>
                </div>

                <div v-else class="max-w-xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-8">
                        <div class="space-y-6">
                            <div>
                                <p class="mb-1 text-xs text-gray-500">Title</p>
                                <p class="border-b border-gray-200 pb-2 font-medium text-gray-900">
                                    {{ form.title }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-6 text-sm">
                                <div>
                                    <p class="text-gray-500">Department</p>
                                    <p class="border-b border-gray-200 pb-1 text-gray-900">{{ project.department }}</p>
                                </div>

                                <div>
                                    <p class="text-gray-500">Academic Year</p>
                                    <p class="border-b border-gray-200 pb-1 text-gray-900">{{ project.academic_year }}</p>
                                </div>

                                <div class="col-span-2">
                                    <p class="text-gray-500">Project Type</p>
                                    <p class="border-b border-gray-200 pb-1 text-gray-900">{{ project.project_type }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm text-gray-600">Abstract</label>
                            <textarea
                                v-model="form.abstract"
                                rows="4"
                                class="mt-2 w-full border-b border-gray-300 py-2 outline-none transition focus:border-[#0C4B05]"
                                placeholder="Write your abstract..."
                            />
                            <p v-if="abstractError" class="mt-1 text-xs text-red-500">
                                {{ abstractError }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm text-gray-600">Upload Manuscript</label>

                            <label class="block cursor-pointer border-b border-gray-300 py-4 transition hover:text-[#0C4B05]">
                                <span class="text-sm">Click to upload or drag file here</span>
                                <input
                                    type="file"
                                    accept=".pdf,.doc,.docx"
                                    @change="handleFileChange"
                                    class="hidden"
                                />
                            </label>

                            <p v-if="form.manuscript" class="mt-2 text-xs text-gray-500">
                                Selected: {{ cleanFileName(form.manuscript.name) }}
                            </p>

                            <p
                                v-else-if="manuscriptSubmitted && manuscriptFileName"
                                class="mt-2 text-xs text-gray-700"
                            >
                                Uploaded: {{ cleanFileName(manuscriptFileName) }}
                            </p>

                            <p v-if="fileError" class="mt-1 text-xs text-red-500">
                                {{ fileError }}
                            </p>
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="isSaving"
                                class="flex w-full items-center justify-center gap-2 rounded-full bg-[#0C4B05] px-5 py-2.5 text-sm text-white transition-all duration-200 hover:bg-[#0a3d04] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50 md:w-auto"
                            >
                                <LoaderCircle
                                    v-if="isSaving"
                                    class="h-4 w-4 animate-spin"
                                />
                                {{ isSaving ? 'Uploading...' : 'Submit Manuscript' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>