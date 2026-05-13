<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { useAlerts } from '@/composables/useAlerts';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen, CalendarDays, ChevronRight, GraduationCap, Save, School } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

type PageProps = {
    project: {
        id: number;
        slug: string;
        title: string;
        project_type?: string | null;
        academic_year?: string | null;
        semester?: string | null;
        adviser?: { name: string } | null;
        researchers: string[];
    };
};

const page = usePage<PageProps>();
const project = computed(() => page.props.project);

const { showSuccessAlert, showErrorAlert, confirmAction } = useAlerts();

const savedTitle = ref(project.value.title || '');
const saveStatus = ref<'saved' | 'editing' | 'error'>('saved');

const form = useForm({
    title: savedTitle.value,
});

const hasTitleChanged = computed(() => {
    return form.title.trim() !== savedTitle.value.trim();
});

const buttonLabel = computed(() => {
    if (saveStatus.value === 'error') return 'Save Failed';
    if (hasTitleChanged.value) return 'Save Changes';
    return 'Saved';
});

const buttonClass = computed(() => {
    if (saveStatus.value === 'error') {
        return 'border border-red-300 bg-red-50 text-red-700 hover:bg-red-100';
    }

    if (hasTitleChanged.value) {
        return 'bg-[#0c4b05] text-white hover:bg-[#0a3d04]';
    }

    return 'cursor-not-allowed border border-gray-300 bg-gray-100 text-gray-600';
});

watch(
    () => form.title,
    () => {
        form.clearErrors('title');

        if (hasTitleChanged.value) {
            saveStatus.value = 'editing';
        } else {
            saveStatus.value = 'saved';
        }
    },
);

const submit = async () => {
    if (!hasTitleChanged.value || form.processing) return;

    const result = await confirmAction('Save Changes?', 'Do you want to save the updated project title?', 'Yes', 'No');

    if (!result.isConfirmed) return;

    form.put(route('student.projects.update', project.value.slug), {
        preserveScroll: true,
        preserveState: true,

        onSuccess: () => {
            savedTitle.value = form.title.trim();
            saveStatus.value = 'saved';

            showSuccessAlert('Saved!', 'Project title has been updated successfully.');
        },

        onError: () => {
            saveStatus.value = 'error';

            showErrorAlert('Save Failed', 'Please check the project title and try again.');
        },
    });
};
</script>

<template>
    <Head title="Edit Project" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex min-h-16 flex-wrap items-center gap-2 border-b px-4 py-3 sm:h-16 sm:flex-nowrap sm:px-6 sm:py-0">
                <SidebarTrigger />

                <Separator orientation="vertical" class="hidden h-4 sm:block" />

                <Breadcrumb class="min-w-0 flex-1">
                    <BreadcrumbList class="flex min-w-0 flex-wrap items-center gap-1 sm:gap-2">
                        <CrumbItem>
                            <Link :href="route('student.dashboard')" class="text-sm font-medium text-gray-500 transition hover:text-gray-800">
                                Dashboard
                            </Link>
                        </CrumbItem>

                        <ChevronRight class="h-4 w-4 shrink-0 text-gray-400" />

                        <CrumbItem class="text-sm font-semibold text-gray-800"> Edit Project </CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-4 p-4 sm:space-y-6 sm:p-6">
                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4 shadow-sm sm:px-6 sm:py-5">
                    <div class="flex flex-col gap-3">
                        <div class="min-w-0">
                            <h2 class="text-xl font-semibold text-gray-900 sm:text-2xl">Edit Project</h2>

                            <p class="mt-1 text-sm leading-6 text-gray-600">
                                Update your project title. Other project details are shown for reference only.
                            </p>
                        </div>

                        <!-- Back Button -->
                        <Link
                            :href="route('student.dashboard')"
                            class="inline-flex w-fit items-center gap-2 text-sm font-semibold text-[#0C4B05] transition hover:underline"
                        >
                            <ArrowLeft class="h-4 w-4 shrink-0" />
                            Back to Dashboard
                        </Link>
                    </div>
                </div>

                <form @submit.prevent="submit" class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="p-4 sm:p-6">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-700"> Project Title </label>

                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[#0c4b05] focus:ring-2 focus:ring-[#0c4b05]/20"
                            />

                            <p v-if="form.errors.title" class="mt-2 text-sm text-red-600">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div class="mt-8">
                            <h3 class="text-base font-bold text-gray-900 sm:text-lg">Project Details</h3>

                            <div class="mt-4 grid grid-cols-1 border-t border-gray-100 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="flex gap-3 border-b border-gray-100 py-5 sm:border-r sm:pr-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100">
                                        <GraduationCap class="h-5 w-5 text-gray-700" />
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-700">Adviser</p>
                                        <p class="break-words text-sm text-gray-600">
                                            {{ project.adviser?.name || 'N/A' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-3 border-b border-gray-100 py-5 sm:px-4 lg:border-r">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100">
                                        <BookOpen class="h-5 w-5 text-gray-700" />
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-700">Type</p>
                                        <p class="break-words text-sm text-gray-600">
                                            {{ project.project_type || 'N/A' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-3 border-b border-gray-100 py-5 sm:border-r sm:pr-4 lg:px-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100">
                                        <CalendarDays class="h-5 w-5 text-gray-700" />
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-700">Academic Year</p>
                                        <p class="break-words text-sm text-gray-600">
                                            {{ project.academic_year || 'N/A' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-3 border-b border-gray-100 py-5 sm:px-4 lg:pl-4 lg:pr-0">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100">
                                        <School class="h-5 w-5 text-gray-700" />
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-700">Semester</p>
                                        <p class="break-words text-sm text-gray-600">
                                            {{ project.semester || 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="project.researchers?.length" class="mt-6">
                            <h3 class="mb-3 text-sm font-semibold text-gray-700">Researchers</h3>

                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="(researcher, index) in project.researchers"
                                    :key="index"
                                    class="max-w-full break-words rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-sm text-gray-700"
                                >
                                    {{ researcher }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-col border-t border-gray-100 pt-6 sm:flex-row sm:justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing || !hasTitleChanged"
                                :class="[
                                    'inline-flex w-full items-center justify-center gap-2 rounded-lg px-6 py-2.5 text-sm font-semibold transition sm:w-auto',
                                    buttonClass,
                                ]"
                            >
                                <Save class="h-4 w-4 shrink-0" />
                                {{ buttonLabel }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>
