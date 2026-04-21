<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Head, usePage, usePoll } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref, watchEffect } from 'vue';

import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

/* ================= TYPES ================= */
type Form = {
    id: number;
    title: string;
    file_name: string;
    section: string;
};

/* ================= DATA ================= */
const page = usePage<{ forms: Form[]; isEligible: boolean }>();

const forms = computed<Form[]>(() => page.props.forms ?? []);
const isEligible = computed<boolean>(() => page.props.isEligible ?? false);

/* ================= GROUPING ================= */
const sectionList = computed<string[]>(() => {
    const uniqueSections: string[] = [];

    forms.value.forEach((form) => {
        if (form.section && !uniqueSections.includes(form.section)) {
            uniqueSections.push(form.section);
        }
    });

    return uniqueSections;
});

const groupedSections = computed(() => {
    const grouped: Record<string, Form[]> = {};

    sectionList.value.forEach((section) => {
        grouped[section] = forms.value.filter((form) => form.section === section);
    });

    return grouped;
});

/* ================= TOGGLE ================= */
const openSections = ref<Record<string, boolean>>({});

const ensureSectionKeysExist = () => {
    sectionList.value.forEach((section) => {
        if (!(section in openSections.value)) {
            openSections.value[section] = false;
        }
    });
};

watchEffect(() => {
    ensureSectionKeysExist();
});

const toggleSection = (section: string) => {
    openSections.value[section] = !openSections.value[section];
};

/* ================= DOWNLOAD ================= */
const downloadFile = (id: number) => {
    window.open(route('student.forms.download', { form: id }), '_blank');
};


usePoll(2000, {
    only: ['forms', 'isEligible'], 
});
</script>

<template>
    <Head title="Forms & Templates" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <!-- HEADER -->
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

                <!-- ✅ HEADER BOX (NEW) -->
                <div class="rounded-2xl border border-gray-200 bg-white px-6 py-6 shadow-sm">
                    <h1 class="text-2xl font-bold text-gray-900">
                        Forms & Templates
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Download available forms and templates for your project.
                    </p>
                </div>

                <!-- EMPTY STATE -->
                <div v-if="!isEligible || forms.length === 0" class="flex min-h-[60vh] items-center justify-center">
                    <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white px-8 py-12 text-center shadow-sm">
                        <p class="text-sm text-gray-500">
                            No Available Forms and Templates.
                        </p>
                    </div>
                </div>

                <!-- SECTIONS -->
                <div v-else class="space-y-4">
                    <div
                        v-for="(sectionForms, sectionName) in groupedSections"
                        :key="sectionName"
                        class="rounded-[20px] border border-gray-200 bg-white px-5 py-4 shadow-sm"
                    >
                        <div
                            class="flex cursor-pointer items-center justify-between"
                            @click="toggleSection(sectionName)"
                        >
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-gray-900">
                                    {{ sectionName }}
                                </h3>

                                <ChevronDown
                                    class="h-5 w-5 transition-transform"
                                    :class="{ 'rotate-180': openSections[sectionName] }"
                                />
                            </div>
                        </div>

                        <div v-if="openSections[sectionName]" class="mt-4 space-y-3">
                            <div
                                v-for="form in sectionForms"
                                :key="form.id"
                                class="flex items-center justify-between border-b border-gray-200 pb-3 last:border-none"
                            >
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ form.title }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ form.file_name }}
                                    </p>
                                </div>

                                <button
                                    @click="downloadFile(form.id)"
                                    class="rounded-lg bg-[#0C4B05] px-4 py-2 text-sm text-white hover:opacity-90"
                                >
                                    Download
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </SidebarInset>
    </SidebarProvider>
</template>