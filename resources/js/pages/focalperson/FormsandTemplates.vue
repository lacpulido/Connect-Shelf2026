<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { useAlerts } from '@/composables/useAlerts';

import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

/* ✅ ALERTS */
const {
    showSuccessAlert,
    showErrorAlert,
    showInfoAlert,
    showWarningAlert,
    confirmDelete,
    showLoadingAlert,
    closeAlert,
} = useAlerts();

/* ================= TYPES ================= */
type Form = {
    id: number;
    title: string;
    file_name: string;
    created_at: string;
    section: string;
};

/* ================= DATA ================= */
const page = usePage<{ forms: Form[] }>();
const forms = ref<Form[]>(page.props.forms ?? []);

/* ================= SECTION ================= */
const sectionList = ref<string[]>([]);

forms.value.forEach((f) => {
    if (f.section && !sectionList.value.includes(f.section)) {
        sectionList.value.push(f.section);
    }
});

/* ================= GROUP ================= */
const groupedSections = computed(() => {
    const grouped: Record<string, Form[]> = {};

    sectionList.value.forEach((section) => {
        grouped[section] = forms.value.filter((f) => f.section === section);
    });

    return grouped;
});

/* ================= TOGGLE ================= */
const openSections = ref<Record<string, boolean>>({});

const toggleSection = (section: string) => {
    openSections.value[section] = !openSections.value[section];
};

/* ================= MODALS ================= */
const showSectionModal = ref(false);
const newSectionName = ref('');

const showUploadModal = ref(false);
const uploadSection = ref('');
const title = ref('');
const selectedFile = ref<File | null>(null);

const isModalOpen = computed(() => {
    return showSectionModal.value || showUploadModal.value;
});

/* ================= ACTIONS ================= */

// ✅ CREATE SECTION
const createSection = () => {
    if (!newSectionName.value.trim()) {
        showWarningAlert('Oops...', 'Section name is required!');
        return;
    }

    if (!sectionList.value.includes(newSectionName.value.trim())) {
        sectionList.value.push(newSectionName.value.trim());
        showSuccessAlert('Section Created!', 'New section added successfully.');
    }

    newSectionName.value = '';
    showSectionModal.value = false;
};

// ✅ OPEN UPLOAD
const openUpload = (sectionName: string) => {
    uploadSection.value = sectionName;
    showUploadModal.value = true;
};

// ✅ FILE VALIDATION
const handleFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;

    if (input.files?.length) {
        const file = input.files[0];
        const allowedTypes = ['application/pdf', 'application/zip'];

        if (!allowedTypes.includes(file.type)) {
            showErrorAlert('Invalid File', 'Only PDF and ZIP files are allowed.');
            selectedFile.value = null;
            return;
        }

        selectedFile.value = file;
    }
};

// ✅ UPLOAD FORM
const uploadForm = () => {
    if (!title.value || !selectedFile.value) {
        showWarningAlert('Missing Fields', 'Please provide a title and file.');
        return;
    }

    const formData = new FormData();
    formData.append('title', title.value);
    formData.append('file', selectedFile.value);
    formData.append('section', uploadSection.value);

    router.post(route('focalperson.forms.store'), formData, {
        forceFormData: true,
        preserveScroll: true,

        onBefore: () => {
            showLoadingAlert('Uploading...');
        },

        onSuccess: () => {
            closeAlert();
            showSuccessAlert('Uploaded!', 'File uploaded successfully.');
            router.reload({ only: ['forms'] });
            resetUpload();
        },

        onError: () => {
            closeAlert();
            showErrorAlert('Failed!', 'Upload failed.');
        },
    });
};

// ✅ RESET
const resetUpload = () => {
    title.value = '';
    selectedFile.value = null;
    showUploadModal.value = false;
};

// ✅ CLOSE MODALS
const closeSectionModal = () => {
    showSectionModal.value = false;
};

const closeUploadModal = () => {
    if (!showUploadModal.value) return;
    resetUpload();
};

// ✅ DOWNLOAD
function downloadFile(id: number) {
    showInfoAlert('Downloading...', 'Your file will open shortly.');
    window.open(route('focalperson.forms.download', { id }), '_blank');
}

// ✅ DELETE FORM
async function deleteForm(id: number) {
    const result = await confirmDelete('Delete?', 'This will be removed.');

    if (result.isConfirmed) {
        router.delete(route('focalperson.forms.destroy', { id }), {
            preserveScroll: true,

            onBefore: () => {
                showLoadingAlert('Deleting...');
            },

            onSuccess: () => {
                closeAlert();
                showSuccessAlert('Deleted!', 'File removed successfully.');
                router.reload({ only: ['forms'] });
            },

            onError: () => {
                closeAlert();
                showErrorAlert('Failed!', 'Delete failed.');
            },
        });
    }
}
</script>

<template>
    <Head title="Forms & Templates" />

    <SidebarProvider>
        <AppSidebar :class="{ 'pointer-events-none opacity-40': isModalOpen }" />

        <SidebarInset :class="{ 'pointer-events-none opacity-40': isModalOpen }">
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem>Forms & Templates</CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        
        <!-- LEFT TEXT -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Available Forms</h2>
            <p class="mt-1 text-sm text-gray-500">
                Manage and organize your forms efficiently.
            </p>
        </div>

        <!-- RIGHT BUTTON -->
        <div>
            <button
                @click="showSectionModal = true"
                class="rounded-md bg-[#0C4B05] px-4 py-2 text-sm text-white hover:opacity-90"
            >
                + Add Section
            </button>
        </div>

    </div>
</div>

                <div v-if="sectionList.length === 0" class="flex justify-center py-16">
                    <div class="w-full max-w-md rounded-2xl border border-dashed px-6 py-10 text-center">
                        <p class="text-sm text-gray-500">
                            No Available Forms and Templates.
                        </p>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="(forms, sectionName) in groupedSections"
                        :key="sectionName"
                        class="rounded-xl border p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div
                                class="flex cursor-pointer items-center gap-2"
                                @click="toggleSection(sectionName)"
                            >
                                <h3 class="font-semibold">{{ sectionName }}</h3>

                                <ChevronDown
                                    class="h-5 w-5 transition-transform"
                                    :class="{ 'rotate-180': openSections[sectionName] }"
                                />
                            </div>

                            <button
                                @click="openUpload(sectionName)"
                                class="text-sm font-medium text-gray-600 hover:underline"
                            >
                                Upload
                            </button>
                        </div>

                        <div v-if="openSections[sectionName]" class="mt-3 space-y-3">
                            <div
                                v-for="form in forms"
                                :key="form.id"
                                class="flex items-center justify-between border-b pb-3 last:border-none"
                            >
                                <div>
                                    <p class="font-medium text-black">{{ form.title }}</p>
                                    <p class="text-sm text-gray-500">{{ form.file_name }}</p>
                                </div>

                                <div class="flex gap-4">
                                    <button
                                        @click="downloadFile(form.id)"
                                        class="text-sm font-medium text-green-600 underline underline-offset-2 transition hover:text-green-700"
                                    >
                                        Download
                                    </button>

                                    <button
                                        @click="deleteForm(form.id)"
                                        class="text-sm font-medium text-red-600 underline underline-offset-2 transition hover:text-red-700"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SidebarInset>

        <!-- CREATE SECTION MODAL -->
        <teleport to="body">
            <div
                v-if="showSectionModal"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 px-4 pt-14 pb-6 backdrop-blur-sm"
                @click.self="closeSectionModal"
            >
                <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <div>
                            <h2 class="text-xl font-bold text-[#0C4B05]">
                                Create Section
                            </h2>
                            <p class="mt-1 text-sm text-gray-500">
                                Add a new section to organize forms and templates.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="closeSectionModal"
                            class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="px-6 py-5">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                            <div class="mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Section Details
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Enter the name of the section you want to create.
                                </p>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Section Name
                                </label>
                                <input
                                    v-model="newSectionName"
                                    type="text"
                                    placeholder="Enter section name"
                                    class="mt-2 w-full border-0 bg-transparent p-0 text-sm font-medium text-gray-800 outline-none placeholder:text-gray-400 focus:ring-0"
                                />
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end gap-3">
                            <button
                                type="button"
                                @click="closeSectionModal"
                                class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                @click="createSection"
                                class="rounded-xl bg-[#0C4B05] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a3d04]"
                            >
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </teleport>

        <!-- UPLOAD MODAL -->
        <teleport to="body">
            <div
                v-if="showUploadModal"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 px-4 pt-14 pb-6 backdrop-blur-sm"
                @click.self="closeUploadModal"
            >
                <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <div>
                            <h2 class="text-xl font-bold text-[#0C4B05]">
                                Upload Form
                            </h2>
                            <p class="mt-1 text-sm text-gray-500">
                                Upload a file to the <span class="font-semibold text-gray-700">{{ uploadSection }}</span> section.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="closeUploadModal"
                            class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="px-6 py-5">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                            <div class="mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Upload Details
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Provide the form title and select a PDF or ZIP file.
                                </p>
                            </div>

                            <div class="space-y-3 text-sm text-gray-700">
                                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Title
                                    </label>
                                    <input
                                        v-model="title"
                                        type="text"
                                        placeholder="Enter form title"
                                        class="mt-2 w-full border-0 bg-transparent p-0 text-sm font-medium text-gray-800 outline-none placeholder:text-gray-400 focus:ring-0"
                                    />
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        File
                                    </label>
                                    <input
                                        type="file"
                                        accept=".pdf,.zip"
                                        @change="handleFileChange"
                                        class="mt-2 block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-[#0C4B05] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#0a3d04]"
                                    />
                                    <p class="mt-2 text-xs text-gray-500">
                                        Accepted file types: PDF, ZIP
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end gap-3">
                            <button
                                type="button"
                                @click="uploadForm"
                                class="rounded-xl bg-[#0C4B05] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a3d04]"
                            >
                                Upload
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </teleport>
    </SidebarProvider>
</template>