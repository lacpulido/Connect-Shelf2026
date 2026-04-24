<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { useAlerts } from '@/composables/useAlerts';
import { Project } from '@/types/project';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Clock3, FileText, LoaderCircle, LockKeyhole, UploadCloud } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface PageProps extends InertiaPageProps {
    flash: {
        success?: string;
        error?: string;
    };
}

const props = defineProps<{
    project: Project | null;
    manuscriptSubmitted: boolean;
    manuscriptStatus: string | null;
    manuscriptFileName: string | null;
    canSubmitFinalManuscript: boolean;
    approvedManuscriptDocument: {
        id: number;
        title: string;
        filename: string;
        status: string;
    } | null;
}>();

const page = usePage<PageProps>();
const flashSuccess = computed(() => page.props.flash?.success || '');

const { showSuccessAlert, showErrorAlert } = useAlerts();

const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

const form = useForm({
    title: props.project?.title ?? '',
    abstract: '',
    manuscript: null as File | null,
});

const abstractError = ref('');
const fileError = ref('');
const isSaving = ref(false);

watch(
    () => props.project,
    (newProject) => {
        form.title = newProject?.title ?? '';
    },
    { immediate: true },
);

watch(
    flashSuccess,
    (value) => {
        if (value) {
            showSuccessAlert('Submitted Successfully', value);
        }
    },
    { immediate: true },
);

function handleFileChange(event: Event) {
    const input = event.target as HTMLInputElement;

    fileError.value = '';
    form.manuscript = null;

    if (!input.files?.length) return;

    const file = input.files[0];

    if (file.size > MAX_FILE_SIZE) {
        fileError.value = 'File is too large. Maximum size is 10MB.';
        input.value = '';
        return;
    }

    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
        fileError.value = 'Only PDF files are allowed.';
        input.value = '';
        return;
    }

    form.manuscript = file;
}

function cleanFileName(name: string | null): string {
    if (!name) return '';
    return name.replace(/^\d+_/, '');
}

function submit() {
    abstractError.value = '';
    fileError.value = '';

    if (!props.canSubmitFinalManuscript) {
        showErrorAlert('Not Allowed', 'Your Manuscript document must be approved by your adviser first before submitting the final manuscript.');
        return;
    }

    let hasError = false;

    if (!form.abstract.trim()) {
        abstractError.value = 'Abstract is required.';
        hasError = true;
    }

    if (!form.manuscript) {
        fileError.value = 'Please upload a manuscript file.';
        hasError = true;
    }

    if (form.manuscript && form.manuscript.size > MAX_FILE_SIZE) {
        fileError.value = 'File is too large. Maximum size is 10MB.';
        hasError = true;
    }

    if (form.manuscript && form.manuscript.type !== 'application/pdf' && !form.manuscript.name.toLowerCase().endsWith('.pdf')) {
        fileError.value = 'Only PDF files are allowed.';
        hasError = true;
    }

    if (hasError) return;

    isSaving.value = true;

    form.post(route('student.submit-final-manuscript.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.title = props.project?.title ?? '';
            isSaving.value = false;
            showSuccessAlert('Submitted Successfully', 'Final manuscript submitted successfully.');
        },
        onError: (errors) => {
            isSaving.value = false;

            if (errors.manuscript) {
                fileError.value = String(errors.manuscript);
                return;
            }

            if (errors.abstract) {
                abstractError.value = String(errors.abstract);
                return;
            }

            const firstError = errors.error || errors.title || 'Something went wrong.';
            showErrorAlert('Submission Failed', String(firstError));
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
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
                        <BreadcrumbItem class="font">Submit Manuscript</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <div class="rounded-2xl border border-gray-200 bg-white px-6 py-6 shadow-sm">
                    <h1 class="text-2xl font-bold text-gray-900">Final Manuscript</h1>
                    <p class="mt-1 text-sm text-gray-500">Submit and manage your final manuscript for project review.</p>
                </div>

                <div v-if="!project" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileText />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Project Found</EmptyTitle>
                        <EmptyDescription>No project found for your account.</EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>

                <div v-else-if="manuscriptStatus === 'approved'" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <CheckCircle2 />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>Approved</EmptyTitle>
                        <EmptyDescription> Your final manuscript has been successfully approved. </EmptyDescription>
                    </Empty>
                </div>

                <div v-else-if="project.adviser_id && manuscriptSubmitted" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Clock3 />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>Pending Review</EmptyTitle>
                        <EmptyDescription>Your manuscript is under review.</EmptyDescription>
                    </Empty>
                </div>

                <div v-else-if="!canSubmitFinalManuscript" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-amber-200 bg-amber-50 shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <LockKeyhole />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>Final Manuscript Locked</EmptyTitle>
                        <EmptyDescription> Your Manuscript document must be approved by your adviser. </EmptyDescription>
                    </Empty>
                </div>

                <div v-else class="w-full rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-8">
                        <div class="space-y-6">
                            <div>
                                <p class="mb-1 text-xs text-gray-500">Title</p>
                                <p class="border-b border-gray-200 pb-2 font-medium text-gray-900">
                                    {{ form.title }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 text-sm md:grid-cols-2 xl:grid-cols-3">
                                <div>
                                    <p class="text-gray-500">Department</p>
                                    <p class="border-b border-gray-200 pb-1 text-gray-900">
                                        {{ project.department }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500">Academic Year</p>
                                    <p class="border-b border-gray-200 pb-1 text-gray-900">
                                        {{ project.academic_year }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500">Project Type</p>
                                    <p class="border-b border-gray-200 pb-1 text-gray-900">
                                        {{ project.project_type }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700">Abstract</label>
                            <textarea
                                v-model="form.abstract"
                                rows="10"
                                class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-[#0C4B05] focus:ring-2 focus:ring-[#0C4B05]/10"
                                placeholder="Write your abstract..."
                            />

                            <p v-if="abstractError" class="mt-2 text-xs text-red-500">
                                {{ abstractError }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700"> Upload Manuscript </label>

                            <label
                                class="flex min-h-[170px] w-full cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-6 py-8 text-center transition hover:border-[#0C4B05] hover:bg-green-50/40"
                                :class="fileError ? 'border-red-300 bg-red-50 hover:border-red-400 hover:bg-red-50' : ''"
                            >
                                <UploadCloud class="mb-3 h-10 w-10" :class="fileError ? 'text-red-500' : 'text-[#0C4B05]'" />

                                <span class="text-sm font-medium text-gray-700"> Click to upload or drag file here </span>

                                <span class="mt-1 text-xs text-gray-500"> Supported files: PDF only, maximum 10MB </span>

                                <input type="file" accept="application/pdf,.pdf" @change="handleFileChange" class="hidden" />
                            </label>

                            <p v-if="form.manuscript" class="mt-3 text-sm text-gray-600">
                                Selected:
                                <span class="font-medium">{{ cleanFileName(form.manuscript.name) }}</span>
                            </p>

                            <p v-if="fileError" class="mt-2 text-xs font-medium text-red-500">
                                {{ fileError }}
                            </p>
                        </div>

                        <div class="pt-2">
                            <div class="flex w-full justify-end">
                                <button
                                    type="submit"
                                    :disabled="isSaving"
                                    class="flex min-w-[190px] items-center justify-center gap-2 rounded-full bg-[#0C4B05] px-10 py-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-[#0a3d04] hover:shadow-md active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <LoaderCircle v-if="isSaving" class="h-4 w-4 animate-spin" />
                                    {{ isSaving ? 'Uploading...' : 'Submit' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>
