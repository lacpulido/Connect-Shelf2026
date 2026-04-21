<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

/* ✅ TYPES */
import type { User } from '@/types/user';

type Project = {
    id: number;
    title: string;
    panelists: User[];
};

const props = defineProps<{
    project: Project;
    faculties: User[];
}>();

/* ================= STATE ================= */

const selectedPanelists = ref<number[]>(props.project.panelists?.map((p) => p.id) ?? []);

const processing = ref(false);
const successMessage = ref<string | null>(null);

/* ================= CONFIG ================= */

const MAX_PANELISTS = 3;

/* ================= ACTION ================= */

const togglePanelist = (id: number) => {
    if (selectedPanelists.value.includes(id)) {
        selectedPanelists.value = selectedPanelists.value.filter((p) => p !== id);
    } else {
        if (selectedPanelists.value.length >= MAX_PANELISTS) {
            alert('Maximum of 3 panelists only');
            return;
        }
        selectedPanelists.value.push(id);
    }
};

const assignPanelists = () => {
    if (selectedPanelists.value.length === 0) {
        alert('Please select at least one panelist');
        return;
    }

    processing.value = true;

    router.post(
        route('faculty.focalperson.projects.assign.store'),
        {
            project_id: props.project.id,
            panelists: selectedPanelists.value,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                successMessage.value = 'Panelists assigned successfully';
            },

            onFinish: () => {
                processing.value = false;

                setTimeout(() => {
                    successMessage.value = null;
                }, 3000);
            },
        },
    );
};
</script>

<template>
    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem class="font-semibold text-[#0C4B05]"> Assign Panelists </CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="p-6">
                <h2 class="mb-6 text-lg font-semibold text-[#0C4B05]">Project: {{ project.title }}</h2>

                <!-- SUCCESS -->
                <div v-if="successMessage" class="mb-4 rounded bg-green-100 p-3 text-green-700">
                    {{ successMessage }}
                </div>

                <div class="rounded-xl border bg-white p-6 shadow">
                    <h3 class="mb-4 font-semibold">Select Panelists</h3>

                    <div class="space-y-2">
                        <label v-for="faculty in faculties" :key="faculty.id" class="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                :checked="selectedPanelists.includes(faculty.id)"
                                @change="togglePanelist(faculty.id)"
                                class="h-4 w-4"
                            />

                            <span> {{ faculty.first_name }} {{ faculty.last_name }} </span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button
                            @click="assignPanelists"
                            :disabled="processing"
                            class="rounded-lg bg-[#0C4B05] px-4 py-2 text-white hover:bg-green-800 disabled:opacity-50"
                        >
                            {{ processing ? 'Saving...' : 'Save Panelists' }}
                        </button>
                    </div>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>
