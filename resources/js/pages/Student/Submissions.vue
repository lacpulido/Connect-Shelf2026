<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { useAlerts } from '@/composables/useAlerts';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { useGroupedSections } from '@/composables/useGroupedSections';
import { useLatestDocuments } from '@/composables/useLatestDocuments';
import { useSubmissionAccordion } from '@/composables/useSubmissionsAccordion';
import { useSubmissionStatus } from '@/composables/useSubmissionStatus';
import { SECTION_DEFINITIONS } from '@/constants/submissionSections';
import type { Project } from '@/types/project';
import type { Submission } from '@/types/submission';
import { parseSubmissionSlug } from '@/utils/parseSubmissionSlug';
import { Head, Link, router, usePage, usePoll } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, CloudUpload, FileText, LoaderCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';

type PageProps = {
    project: Project | null;
    documents: Submission[][];
    flash?: {
        success?: string;
        error?: string;
    };
};

type SectionItem = {
    key: string;
    label: string;
    documents: Submission[];
};

const page = usePage<PageProps>();
const project = computed(() => page.props.project ?? null);
const documents = computed<Submission[][]>(() => page.props.documents ?? []);

const latestDocuments = useLatestDocuments(documents);
const { statusClasses, statusLabel } = useSubmissionStatus();
const { formatDateTime } = useDateFormatter();

const { groupedSections } = useGroupedSections(latestDocuments, SECTION_DEFINITIONS);

const { toggleSection, isOpen } = useSubmissionAccordion(
    SECTION_DEFINITIONS,
    computed(() => page.url),
    documents,
);

const {
    showSuccessAlert,
    showErrorAlert,
    showInfoAlert,
    confirmDelete,
} = useAlerts();

const fileInputs = ref<Record<string | number, HTMLInputElement | null>>({});
const dragActiveSections = ref<Record<string, boolean>>({});
const uploadingSections = ref<Record<string, boolean>>({});

const hasProject = computed(() => !!project.value);

const isProjectCompleted = computed(() => {
    if (!project.value) return false;

    return String(project.value.status ?? '').toLowerCase() === 'completed' || !!project.value.completed_at;
});

function setDragState(sectionKey: string, value: boolean) {
    dragActiveSections.value[sectionKey] = value;
}

function isDragging(sectionKey: string) {
    return !!dragActiveSections.value[sectionKey];
}

function isUploading(sectionKey: string) {
    return !!uploadingSections.value[sectionKey];
}

function triggerFileInput(key: string | number) {
    if (!hasProject.value) {
        showErrorAlert('No Project Found', 'Create a project first before submitting files.');
        return;
    }

    if (isProjectCompleted.value) {
        showInfoAlert('Upload Disabled', 'Uploads are no longer allowed because this project is already completed.');
        return;
    }

    fileInputs.value[key]?.click();
}

function validatePdfFile(file: File | null): boolean {
    if (!file) return false;

    const isPdfMime = file.type === 'application/pdf';
    const isPdfExtension = file.name.toLowerCase().endsWith('.pdf');

    if (!isPdfMime && !isPdfExtension) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid File',
            text: 'Only PDF files are allowed.',
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'OK',
            width: 320,
            padding: '1rem',
            customClass: {
                popup: 'rounded-xl',
                title: 'text-lg font-semibold',
                htmlContainer: 'text-sm',
                confirmButton: 'px-4 py-1.5 text-sm rounded-md',
            },
        });
        return false;
    }

    return true;
}

function canRemoveFile(doc: Submission) {
    if (!hasProject.value) return false;
    if (isProjectCompleted.value) return false;

    const unreviewedStatuses = ['pending', 'submitted'];
    return !!doc.file_url && unreviewedStatuses.includes(String(doc.status ?? '').toLowerCase());
}

function canUploadNew(section: SectionItem) {
    return hasProject.value && !isProjectCompleted.value && section.documents.length === 0;
}

function canResubmit(doc: Submission) {
    return hasProject.value && !isProjectCompleted.value && String(doc.status ?? '').toLowerCase() === 'needs_revision';
}

function uploadNewFile(section: SectionItem, file: File | null) {
    if (!hasProject.value) {
        showErrorAlert('No Project Found', 'Create a project first before submitting files.');
        return;
    }

    if (isProjectCompleted.value) {
        showInfoAlert('Upload Disabled', 'Uploads are no longer allowed because this project is already completed.');
        return;
    }

    if (!validatePdfFile(file)) {
        const input = fileInputs.value[`new-${section.key}`];
        if (input) input.value = '';
        return;
    }

    const formData = new FormData();
    formData.append('title', section.label);
    formData.append('document', file as File);

    uploadingSections.value[section.key] = true;

    router.post(route('student.submit-paper.store'), formData, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Submitted',
                text: 'Paper submitted successfully.',
                confirmButtonColor: '#0C4B05',
                confirmButtonText: 'OK',
                width: 320,
                padding: '1rem',
                iconColor: '#16a34a',
                customClass: {
                    popup: 'rounded-xl',
                    title: 'text-lg font-semibold',
                    htmlContainer: 'text-sm',
                    confirmButton: 'px-4 py-1.5 text-sm rounded-md',
                },
            });

            const input = fileInputs.value[`new-${section.key}`];
            if (input) input.value = '';

            router.reload({ only: ['documents', 'project'] });
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Submission Failed',
                text: Object.values(errors)[0] || 'Please check your inputs and try again.',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'OK',
                width: 320,
                padding: '1rem',
                customClass: {
                    popup: 'rounded-xl',
                    title: 'text-lg font-semibold',
                    htmlContainer: 'text-sm',
                    confirmButton: 'px-4 py-1.5 text-sm rounded-md',
                },
            });

            const input = fileInputs.value[`new-${section.key}`];
            if (input) input.value = '';
        },
        onFinish: () => {
            uploadingSections.value[section.key] = false;
            dragActiveSections.value[section.key] = false;
        },
    });
}

function handleNewUploadChange(event: Event, section: SectionItem) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    uploadNewFile(section, file);
}

function handleDropUpload(event: DragEvent, section: SectionItem) {
    event.preventDefault();
    setDragState(section.key, false);

    if (!canUploadNew(section)) return;

    const file = event.dataTransfer?.files?.[0] ?? null;
    uploadNewFile(section, file);
}

function handleResubmit(event: Event, submissionId: number) {
    if (!hasProject.value) {
        showErrorAlert('No Project Found', 'Create a project first before submitting files.');
        return;
    }

    if (isProjectCompleted.value) {
        showInfoAlert('Resubmission Disabled', 'You can no longer resubmit because this project is already completed.');
        return;
    }

    const input = event.target as HTMLInputElement;
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];

    if (!validatePdfFile(file)) {
        input.value = '';
        return;
    }

    const formData = new FormData();
    formData.append('document', file);

    router.post(route('student.submissions.resubmit', { submission: submissionId }), formData, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showSuccessAlert('Resubmission Successful', 'Your document has been resubmitted successfully.');
            input.value = '';
            router.reload({ only: ['documents', 'project'] });
        },
        onError: () => {
            showErrorAlert('Upload Failed', 'Something went wrong while resubmitting the document.');
        },
    });
}

function handleDelete(submissionId: number) {
    if (!hasProject.value) {
        showErrorAlert('No Project Found', 'You cannot manage submissions without a project.');
        return;
    }

    if (isProjectCompleted.value) {
        showInfoAlert('Removal Disabled', 'You can no longer remove files because this project is already completed.');
        return;
    }

    confirmDelete('Remove submission?', 'This action cannot be undone.').then((result) => {
        if (result.isConfirmed) {
            router.delete(route('student.submissions.destroy', { id: submissionId }), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    showSuccessAlert('File Removed', 'The uploaded file has been removed successfully.');
                    router.reload({ only: ['documents', 'project'] });
                },
                onError: () => {
                    showErrorAlert('Delete Failed', 'Something went wrong while removing the file.');
                },
            });
        }
    });
}

onMounted(() => {
    if (page.props.flash?.success) {
        showSuccessAlert('Success!', page.props.flash.success);
    }

    if (page.props.flash?.error) {
        showErrorAlert('Error', page.props.flash.error);
    }
});

usePoll(2000, {
    only: ['documents', 'project'],
});
</script>

<template>
    <Head title="My Submissions" />

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
                    <h1 class="text-xl font-semibold text-gray-900">Submissions</h1>
                    <p class="mt-1 text-sm text-gray-500">Upload and manage your project documents.</p>
                </div>

                <div
                    v-if="!hasProject"
                    class="rounded-2xl border border-green-200 bg-green-50 px-6 py-6 text-center shadow-sm"
                >
                    <p class="text-sm text-green-700">
                        Create a project first before you can upload or manage submissions.
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
                                <ChevronUp v-if="isOpen(section.key)" class="h-4 w-4 text-gray-700" />
                                <ChevronDown v-else class="h-4 w-4 text-gray-700" />
                            </span>
                        </button>

                        <div v-if="isOpen(section.key)" class="mt-5">
                            <div
                                v-if="canUploadNew(section)"
                                class="mb-5"
                            >
                                <div
                                    role="button"
                                    tabindex="0"
                                    @click="triggerFileInput(`new-${section.key}`)"
                                    @keydown.enter.prevent="triggerFileInput(`new-${section.key}`)"
                                    @keydown.space.prevent="triggerFileInput(`new-${section.key}`)"
                                    @dragover.prevent="setDragState(section.key, true)"
                                    @dragleave.prevent="setDragState(section.key, false)"
                                    @drop="handleDropUpload($event, section)"
                                    :class="[
                                        'group flex min-h-[220px] w-full cursor-pointer flex-col items-center justify-center rounded-[28px] border border-dashed px-6 py-10 text-center transition-all duration-200',
                                        isDragging(section.key)
                                            ? 'border-[#0C4B05] bg-green-50 shadow-sm'
                                            : 'border-gray-300 bg-[#fafafa] hover:border-[#0C4B05] hover:bg-green-50/40',
                                        isUploading(section.key) ? 'pointer-events-none opacity-70' : '',
                                    ]"
                                >
                                    <div
                                        class="mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-green-50"
                                    >
                                        <LoaderCircle
                                            v-if="isUploading(section.key)"
                                            class="h-10 w-10 animate-spin text-[#0C4B05]"
                                        />
                                        <CloudUpload
                                            v-else
                                            class="h-10 w-10 text-[#0C4B05]"
                                        />
                                    </div>

                                    <h3 class="text-[18px] font-semibold text-gray-900">
                                        {{ isUploading(section.key) ? 'Uploading file...' : 'Click to upload file' }}
                                    </h3>

                                    <p class="mt-2 text-sm text-gray-500">
                                        Supported files: PDF
                                    </p>
                                </div>

                                <input
                                    type="file"
                                    accept="application/pdf"
                                    class="hidden"
                                    :ref="(el) => (fileInputs[`new-${section.key}`] = el as HTMLInputElement | null)"
                                    @change="(e) => handleNewUploadChange(e, section)"
                                />
                            </div>

                            <div
                                v-else-if="isProjectCompleted && section.documents.length === 0"
                                class="mb-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500"
                            >
                                Project is already completed.
                            </div>

                            <div
                                v-if="section.documents.length > 0"
                                class="space-y-5"
                            >
                                <div
                                    v-for="(doc, index) in section.documents"
                                    :key="doc.id"
                                >
                                    <div class="rounded-2xl border border-gray-200 bg-white p-5 transition hover:shadow-sm">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900">
                                                    Version {{ index + 1 }}
                                                </span>

                                                <span
                                                    v-if="doc.status"
                                                    :class="[
                                                        'rounded-full px-3 py-1 text-xs font-medium capitalize',
                                                        statusClasses(doc.status),
                                                    ]"
                                                >
                                                    {{ statusLabel(doc.status) }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mt-4 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                            <div class="flex items-start gap-3">
                                                <div class="mt-0.5 rounded-xl bg-white p-2 shadow-sm">
                                                    <FileText class="h-5 w-5 text-[#0C4B05]" />
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <template v-if="doc.file_url">
                                                        <a
                                                            :href="doc.file_url"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="block break-words text-sm font-semibold text-gray-900 hover:text-[#0C4B05] hover:underline"
                                                        >
                                                            {{ doc.filename }}
                                                        </a>
                                                    </template>

                                                    <p
                                                        v-else
                                                        class="block break-words text-sm font-semibold text-gray-900"
                                                    >
                                                        {{ doc.filename }}
                                                    </p>

                                                    <p class="mt-1 text-xs text-gray-500">
                                                        Uploaded on {{ formatDateTime(doc.created_at) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 border-t border-gray-200 pt-3">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                                Comments
                                            </p>

                                            <div
                                                v-if="doc.comments?.length && doc.comments[0]?.comment?.trim()"
                                                class="mt-2"
                                            >
                                                <p class="text-sm leading-relaxed text-gray-700">
                                                    {{ doc.comments[0].comment }}
                                                </p>
                                            </div>

                                            <div v-else class="mt-2">
                                                <p class="text-sm italic text-gray-400">
                                                    No comment provided
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap justify-end gap-2 pt-4">
                                            <button
                                                v-if="canRemoveFile(doc)"
                                                type="button"
                                                @click="handleDelete(doc.id)"
                                                class="inline-flex min-w-[140px] items-center justify-center rounded-md bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:opacity-90"
                                            >
                                                Remove File
                                            </button>

                                            <button
                                                v-if="canResubmit(doc)"
                                                type="button"
                                                @click="triggerFileInput(doc.id)"
                                                class="inline-flex min-w-[140px] items-center justify-center rounded-md bg-[#0C4B05] px-4 py-2.5 text-sm font-medium text-white transition hover:opacity-90"
                                            >
                                                Resubmit
                                            </button>

                                            <Link
                                                v-if="project && doc.slug"
                                                :href="
                                                    (() => {
                                                        const { folder, document } = parseSubmissionSlug(doc.slug);
                                                        return route('student.submissions.show', {
                                                            project: project.slug,
                                                            folder,
                                                            document,
                                                            open: section.key,
                                                        });
                                                    })()
                                                "
                                                class="inline-flex min-w-[140px] items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                                            >
                                                View History
                                            </Link>

                                            <input
                                                type="file"
                                                accept="application/pdf"
                                                class="hidden"
                                                :ref="(el) => (fileInputs[doc.id] = el as HTMLInputElement | null)"
                                                @change="(e) => handleResubmit(e, doc.id)"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center"
                            >
                                <p class="text-sm text-gray-500">No file submitted for {{ section.label }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>