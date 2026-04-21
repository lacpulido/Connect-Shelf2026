<script setup lang="ts">
import { FileText } from 'lucide-vue-next';
import { ref } from 'vue';

interface Project {
    id: number;
    type: 'thesis' | 'capstone';
    title: string;
    author: string;
    department: string;
    year: string;
    abstract: string;
}

const props = defineProps<{
    projects: Project[];
}>();

const selectedProject = ref<Project | null>(null);

const viewDetails = (project: Project) => {
    selectedProject.value = project;
};

const closeModal = () => {
    selectedProject.value = null;
};

const downloadPdf = (projectId: number) => {
    window.open(`/manuscripts/${projectId}/download`, '_blank');
};
</script>

<template>
    <section class="px-6 py-16 pb-24">
        <div class="mx-auto max-w-7xl">
            <!-- HEADER -->
            <div class="mb-12 flex flex-col justify-between md:flex-row md:items-end">
                <div>
                    <h2 class="mb-3 text-5xl text-[#0C4B05]">
                        Featured <span class="text-[#FFCD00]">Projects</span>
                    </h2>

                    <p class="text-lg text-gray-600">
                        Discover the latest research and academic work
                    </p>
                </div>
            </div>

            <!-- EMPTY STATE -->
            <div
                v-if="!props.projects || props.projects.length === 0"
                class="py-10 text-center text-gray-500"
            >
                No featured projects available yet.
            </div>

            <!-- PROJECT LIST -->
            <div v-else class="space-y-6">
                <div
                    v-for="project in props.projects"
                    :key="project.id"
                    class="group rounded-2xl border-2 border-gray-100 bg-white p-8 shadow-md transition-all duration-300 hover:border-[#FFCD00] hover:shadow-xl"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div class="flex-1">
                            <div class="mb-3 flex items-center gap-3">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1.5 rounded-xl px-4 py-1.5 text-xs',
                                        project.type === 'thesis'
                                            ? 'bg-[#FFCD00] text-[#0C4B05]'
                                            : 'bg-[#0C4B05] text-white',
                                    ]"
                                >
                                    <FileText class="h-3.5 w-3.5" />
                                    {{ project.type === 'thesis' ? 'Thesis' : 'Capstone' }}
                                </span>

                                <span
                                    class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700"
                                >
                                    {{ project.year }}
                                </span>
                            </div>

                            <h3
                                class="mb-3 text-2xl text-gray-900 transition-colors group-hover:text-[#0C4B05]"
                            >
                                {{ project.title }}
                            </h3>

                            <p class="mb-4 line-clamp-2 text-gray-600">
                                {{ project.abstract }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end border-t border-gray-100 pt-4">
                        <button
                            @click="viewDetails(project)"
                            class="rounded-xl bg-[#0C4B05] px-6 py-2.5 text-white shadow-md transition-all duration-300 hover:bg-[#0C4B05]/90 hover:shadow-lg"
                        >
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <div
            v-if="selectedProject"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white">
                <div class="border-b p-6">
                    <h2 class="text-2xl font-semibold text-gray-800">
                        {{ selectedProject.title }}
                    </h2>
                </div>

                <div class="space-y-6 p-6">
                    <div>
                        <h3 class="mb-3 font-semibold text-[#0C4B05]">Abstract</h3>
                        <p class="text-justify leading-relaxed text-gray-700">
                            {{ selectedProject.abstract }}
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button
                            @click="downloadPdf(selectedProject.id)"
                            class="flex-1 rounded-xl bg-[#0C4B05] py-3 text-white transition hover:opacity-90"
                        >
                            Download PDF
                        </button>

                        <button
                            @click="closeModal"
                            class="rounded-xl bg-gray-200 px-6 py-3 transition hover:bg-gray-300"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>