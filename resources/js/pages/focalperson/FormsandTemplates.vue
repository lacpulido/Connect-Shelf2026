<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbItem as CrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { useAlerts } from '@/composables/useAlerts';
import { Head, router, usePage, usePoll } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

const {
    showSuccessAlert,
    showInfoAlert,
    showWarningAlert,
    confirmDelete,
    showLoadingAlert,
    closeAlert,
} = useAlerts();

type Form = {
    id: number;
    title: string;
    file_name: string;
    created_at: string;
    section: string;
};

const page = usePage<{ forms: Form[]; errors?: Record<string, string> }>();

const forms = ref<Form[]>(page.props.forms ?? []);
const manualSections = ref<string[]>([]);
const fileInputs = ref<Record<string, HTMLInputElement | null>>({});
const sectionErrors = ref<Record<string, string>>({});
const uploadingSection = ref<string | null>(null);

usePoll(3000, {
    only: ['forms'],
    preserveScroll: true,
});

watch(
    () => page.props.forms,
    (newForms) => {
        forms.value = newForms ?? [];
    },
    { immediate: true },
);

const sectionList = computed(() => {
    const databaseSections = forms.value.map((f) => f.section).filter(Boolean);
    return [...new Set([...databaseSections, ...manualSections.value])];
});

const groupedSections = computed(() => {
    const grouped: Record<string, Form[]> = {};

    sectionList.value.forEach((section) => {
        grouped[section] = forms.value.filter((f) => f.section === section);
    });

    return grouped;
});

const openSections = ref<Record<string, boolean>>({});
const showSectionModal = ref(false);
const newSectionName = ref('');

const isModalOpen = computed(() => showSectionModal.value);

const toggleSection = (section: string) => {
    openSections.value[section] = !openSections.value[section];
};

const createSection = () => {
    if (!newSectionName.value.trim()) {
        showWarningAlert('Oops...', 'Section name is required!');
        return;
    }

    const sectionName = newSectionName.value.trim();

    if (!sectionList.value.includes(sectionName)) {
        manualSections.value.push(sectionName);
        openSections.value[sectionName] = true;
        showSuccessAlert('Section Created!', 'New section added successfully.');
    }

    newSectionName.value = '';
    showSectionModal.value = false;
};

const closeSectionModal = () => {
    showSectionModal.value = false;
};

const setFileInput = (sectionName: string, el: HTMLInputElement | null) => {
    fileInputs.value[sectionName] = el;
};

const openUpload = async (sectionName: string) => {
    sectionErrors.value[sectionName] = '';

    await nextTick();

    fileInputs.value[sectionName]?.click();
};

const handleDirectFileUpload = (event: Event, sectionName: string) => {
    const input = event.target as HTMLInputElement;
    sectionErrors.value[sectionName] = '';

    if (!input.files?.length) return;

    const file = input.files[0];
    const maxSize = 10 * 1024 * 1024;

    if (file.size > maxSize) {
        sectionErrors.value[sectionName] = 'File is too large. Maximum file size is 10MB.';
        input.value = '';
        return;
    }

    const formData = new FormData();
    formData.append('title', file.name.replace(/\.[^/.]+$/, ''));
    formData.append('file', file);
    formData.append('section', sectionName);

    router.post(route('focalperson.forms.store'), formData, {
        forceFormData: true,
        preserveScroll: true,

        onBefore: () => {
            uploadingSection.value = sectionName;
        },

        onSuccess: () => {
            router.reload({
                only: ['forms'],
                preserveScroll: true,
            });
            input.value = '';
            uploadingSection.value = null;
            sectionErrors.value[sectionName] = '';
        },

        onError: (errors) => {
            input.value = '';
            uploadingSection.value = null;

            sectionErrors.value[sectionName] =
                errors.file ||
                errors.title ||
                errors.section ||
                'Upload failed. Please try again.';
        },

        onFinish: () => {
            uploadingSection.value = null;
        },
    });
};

function downloadFile(id: number) {
    showInfoAlert('Downloading...', 'Your file will open shortly.');
    window.open(route('focalperson.forms.download', { id }), '_blank');
}

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
                router.reload({
                    only: ['forms'],
                    preserveScroll: true,
                });
            },

            onError: () => {
                closeAlert();
                sectionErrors.value.general = 'Delete failed. Please try again.';
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
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Available Forms</h2>
                            <p class="mt-1 text-sm text-gray-500">
                                Manage and organize your forms efficiently.
                            </p>
                        </div>

                        <button
                            @click="showSectionModal = true"
                            class="rounded-md bg-[#0C4B05] px-4 py-2 text-sm text-white hover:opacity-90"
                        >
                            + Add Section
                        </button>
                    </div>
                </div>

                <p
                    v-if="sectionErrors.general"
                    class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600"
                >
                    {{ sectionErrors.general }}
                </p>

                <div v-if="sectionList.length === 0" class="flex justify-center py-16">
                    <div class="w-full max-w-md rounded-2xl border border-dashed px-6 py-10 text-center">
                        <p class="text-sm text-gray-500">
                            No Available Forms and Templates.
                        </p>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="(sectionForms, sectionName) in groupedSections"
                        :key="sectionName"
                        class="rounded-xl border p-4"
                    >
                        <input
                            type="file"
                            class="hidden"
                            :ref="(el) => setFileInput(String(sectionName), el as HTMLInputElement | null)"
                            @change="handleDirectFileUpload($event, String(sectionName))"
                        />

                        <div class="flex items-center justify-between">
                            <div
                                class="flex cursor-pointer items-center gap-2"
                                @click="toggleSection(String(sectionName))"
                            >
                                <h3 class="font-semibold">{{ sectionName }}</h3>

                                <ChevronDown
                                    class="h-5 w-5 transition-transform"
                                    :class="{ 'rotate-180': openSections[String(sectionName)] }"
                                />
                            </div>

                            <button
                                type="button"
                                @click="openUpload(String(sectionName))"
                                :disabled="uploadingSection === String(sectionName)"
                                class="text-sm font-medium text-gray-600 hover:underline disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{ uploadingSection === String(sectionName) ? 'Uploading...' : 'Upload' }}
                            </button>
                        </div>

                        <p
                            v-if="sectionErrors[String(sectionName)]"
                            class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-600"
                        >
                            {{ sectionErrors[String(sectionName)] }}
                        </p>

                        <div v-if="openSections[String(sectionName)]" class="mt-3 space-y-3">
                            <div
                                v-for="form in sectionForms"
                                :key="form.id"
                                class="flex items-center justify-between border-b pb-3 last:border-none"
                            >
                                <div>
                                    <p class="font-medium text-black">{{ form.title }}</p>
                                    <p class="text-sm text-gray-500">{{ form.file_name }}</p>
                                </div>

                                <div class="flex gap-4">
                                  
                        

                                    <button
                                        @click="deleteForm(form.id)"
                                        class="text-sm font-medium text-red-600 underline underline-offset-2 transition hover:text-red-700"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="sectionForms.length === 0"
                                class="rounded-lg border border-dashed p-4 text-center text-sm text-gray-500"
                            >
                                No files uploaded in this section yet.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SidebarInset>

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
    </SidebarProvider>
</template>