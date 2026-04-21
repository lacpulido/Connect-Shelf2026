<script setup lang="ts">
import Header from '@/components/Navbar.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Download, FileText } from 'lucide-vue-next';
import { computed } from 'vue';

type Researcher = {
    first_name?: string;
    middle_name?: string;
    last_name?: string;
    extension_name?: string;
};

type Manuscript = {
    id?: number | string;
};

type Department = {
    name?: string;
};

type Project = {
    title?: string;
    abstract?: string;
    created_at?: string;
    project_type?: string;
    researchers?: Researcher[];
    manuscript?: Manuscript | null;
    department?: Department | null;
};

type PageProps = {
    auth: {
        user: unknown | null;
    };
};

const props = defineProps<{
    project: Project;
}>();

const page = usePage<PageProps>();

const formattedResearchers = computed(() => {
    return (
        (props.project.researchers ?? [])
            .filter((u) => u)
            .map((u) => {
                return [u.first_name, u.middle_name, u.last_name, u.extension_name]
                    .filter(Boolean)
                    .join(' ');
            })
            .join(', ') || 'No researchers available'
    );
});

const manuscriptDownloadUrl = computed(() => {
    if (!props.project?.manuscript?.id) return null;
   return route('manuscripts.download', {
    id: props.project.manuscript.id,
});
});

const isAuthenticated = computed(() => {
    return !!page.props.auth?.user;
});

const documentActionUrl = computed(() => {
    if (!manuscriptDownloadUrl.value) return null;

    if (isAuthenticated.value) {
        return manuscriptDownloadUrl.value;
    }

    return route('login', {
        download: manuscriptDownloadUrl.value,
    });
});

const formattedDate = computed(() => {
    if (!props.project?.created_at) return 'N/A';

    return new Date(props.project.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});
</script>

<template>
    <Head title="Project Details" />
    <Header />

    <div class="mx-auto max-w-6xl p-6">
        <a
            href="javascript:history.back()"
            class="mb-4 inline-block text-sm font-medium text-gray-600 hover:text-[#0C4B05] hover:underline"
        >
            ← Back To Projects
        </a>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="md:col-span-2">
                <div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
                    <div class="border-b bg-[#0C4B05] px-6 py-4">
                        <h2 class="text-lg font-semibold text-white">Project Information</h2>
                    </div>

                    <div class="divide-y text-sm">
                        <div class="grid grid-cols-3 px-6 py-4">
                            <div class="font-medium text-gray-500">Researchers</div>
                            <div class="col-span-2 text-gray-800">
                                {{ formattedResearchers }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 px-6 py-4">
                            <div class="font-medium text-gray-500">Project Title</div>
                            <div class="col-span-2 font-semibold text-gray-900">
                                {{ project.title || 'N/A' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 px-6 py-4">
                            <div class="font-medium text-gray-500">Date Approved</div>
                            <div class="col-span-2 text-gray-800">
                                {{ formattedDate }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 px-6 py-4">
                            <div class="font-medium text-gray-500">Abstract</div>
                            <div class="col-span-2 text-justify leading-relaxed text-gray-700">
                                {{ project.abstract || 'No abstract available' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 px-6 py-4">
                            <div class="font-medium text-gray-500">Degree</div>
                            <div class="col-span-2 text-gray-800">
                                {{ project.department?.name || 'N/A' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 px-6 py-4">
                            <div class="font-medium text-gray-500">Project Type</div>
                            <div class="col-span-2 text-gray-800">
                                {{ project.project_type || 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="rounded-2xl border bg-white p-6 text-center shadow-sm transition hover:shadow-md">
                    <h3 class="mb-4 text-lg font-semibold text-gray-700">Document</h3>

                    <div class="flex flex-col items-center">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                            <FileText class="h-8 w-8 text-gray-600" />
                        </div>

                        <a
                            v-if="documentActionUrl"
                            :href="documentActionUrl"
                            class="inline-flex items-center gap-2 rounded-lg bg-[#0C4B05] px-5 py-2 text-sm font-medium text-white shadow transition hover:bg-[#0a3d04]"
                        >
                            <Download class="h-4 w-4" />
                            Download PDF
                        </a>

                        <div v-else class="mt-2 text-sm text-gray-400">
                            No file available
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>