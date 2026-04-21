<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import SubmitButton from '@/components/SubmitFile.vue';
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
import { ChevronDown, ChevronUp } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

type PageProps = {
    project: Project | null;
    documents: Submission[][];
    flash?: {
        success?: string;
        error?: string;
    };
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

const fileInputs = ref<Record<number, HTMLInputElement | null>>({});

const hasProject = computed(() => !!project.value);

const isProjectCompleted = computed(() => {
    if (!project.value) return false;

    return String(project.value.status ?? '').toLowerCase() === 'completed' || !!project.value.completed_at;
});

function triggerFileInput(id: number) {
    if (!hasProject.value) {
        showErrorAlert('No Project Found', 'Create a project first before submitting files.');
        return;
    }

    if (isProjectCompleted.value) {
        showInfoAlert('Upload Disabled', 'Uploads are no longer allowed because this project is already completed.');
        return;
    }

    fileInputs.value[id]?.click();
}

function canRemoveFile(doc: Submission) {
    if (!hasProject.value) return false;
    if (isProjectCompleted.value) return false;

    const unreviewedStatuses = ['pending', 'submitted'];
    return !!doc.file_url && unreviewedStatuses.includes(String(doc.status ?? '').toLowerCase());
}

function canUploadNew(section: { documents: Submission[] }) {
    return hasProject.value && !isProjectCompleted.value && section.documents.length === 0;
}

function canResubmit(doc: Submission) {
    return hasProject.value && !isProjectCompleted.value && String(doc.status ?? '').toLowerCase() === 'needs_revision';
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

    const formData = new FormData();
    formData.append('document', input.files[0]);

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
                            <div v-if="canUploadNew(section)" class="mb-5 flex justify-end">
                                <SubmitButton label="Upload File" :section-title="section.label" />
                            </div>

                            <div
                                v-else-if="isProjectCompleted && section.documents.length === 0"
                                class="mb-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500"
                            >
                                Upload disabled because the project is already completed.
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

                                            <span class="text-xs text-gray-400">
                                                {{ formatDateTime(doc.created_at) }}
                                            </span>
                                        </div>

                                        <div class="mt-3">
                                            <template v-if="doc.file_url">
                                                <a
                                                    :href="doc.file_url"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="break-words text-sm font-medium text-gray-900 hover:underline"
                                                >
                                                    {{ doc.filename }}
                                                </a>
                                            </template>

                                            <p v-else class="break-words text-sm font-medium text-gray-900">
                                                {{ doc.filename }}
                                            </p>
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
                                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700"
                                            >
                                                Remove File
                                            </button>

                                            <button
                                                v-if="canResubmit(doc)"
                                                type="button"
                                                @click="triggerFileInput(doc.id)"
                                                class="rounded-lg bg-[#0C4B05] px-4 py-2 text-sm font-medium text-white transition hover:opacity-90"
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
                                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
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