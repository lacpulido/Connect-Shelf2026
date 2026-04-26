<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbItem as CrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { Head, router } from '@inertiajs/vue3';
import { FolderOpen } from 'lucide-vue-next';

type Department = {
    name?: string | null;
};

type Person = {
    id?: number;
    first_name?: string | null;
    last_name?: string | null;
    name?: string | null;
    department?: Department | null;
};

type Adviser = {
    id: number;
    first_name: string;
    last_name: string;
};

type Schedule = {
    defense_date?: string | null;
    defense_time?: string | null;
    venue?: string | null;
};

type Project = {
    id: number;
    slug: string;
    title: string;
    abstract?: string | null;
    project_type?: string | null;
    academic_year?: string | null;
    semester?: string | null;
    status?: string | null;
    adviser?: Adviser | null;
    schedule?: Schedule | null;
    student?: Person | null;
    creator?: Person | null;
    user?: Person | null;
    leader?: Person | null;
};

defineProps<{
    projects: Project[];
}>();

const goToManageProject = (project: Project) => {
    router.visit(
        route('focalperson.projects.manage', {
            project: project.slug,
        }),
    );
};

const getProjectOwner = (project: Project): Person | null => {
    return project.leader ?? project.student ?? project.creator ?? project.user ?? null;
};

const getFullName = (person: Person | null): string => {
    if (!person) return 'No student assigned';

    if (person.name) return person.name;

    const fullName = `${person.first_name ?? ''} ${person.last_name ?? ''}`.trim();

    return fullName || 'No student assigned';
};

const getInitials = (person: Person | null): string => {
    const name = getFullName(person);

    if (name === 'No student assigned') return 'NA';

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join('');
};

const getDepartment = (person: Person | null): string => {
    return person?.department?.name ?? 'No department';
};

const getAdviserName = (project: Project): string => {
    if (!project.adviser) return 'N/A';

    return `${project.adviser.first_name} ${project.adviser.last_name}`.trim();
};
</script>

<template>
    <Head title="Projects" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem>Projects</CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <main class="space-y-5 p-6">
                <div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
                            <p class="mt-1 text-sm text-gray-500">
                                Manage panelists and defense schedules for each project.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="!projects.length" class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FolderOpen />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Projects Found</EmptyTitle>
                        <EmptyDescription>Projects will appear here once available.</EmptyDescription>
                        <EmptyContent />
                    </Empty>
                </div>

                <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="project in projects"
                        :key="project.id"
                        class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div class="min-h-[128px] border-b border-gray-100 bg-gradient-to-r from-[#0C4B05] to-[#146b0c] px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-white/80">
                                        {{ project.project_type ?? 'No project type' }}
                                    </p>

                                    <h2 class="line-clamp-3 text-lg font-bold leading-snug text-white">
                                        {{ project.title }}
                                    </h2>
                                </div>

                                <span class="shrink-0 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold capitalize text-white">
                                    {{ project.status ?? 'Pending' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    Project Leader 
                                </p>

                                <div class="mt-3 flex items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#0C4B05]/10 text-sm font-bold uppercase text-[#0C4B05]"
                                    >
                                        {{ getInitials(getProjectOwner(project)) }}
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-base font-semibold text-gray-900">
                                            {{ getFullName(getProjectOwner(project)) }}
                                        </p>
                                        <p class="truncate text-sm text-gray-500">
                                            {{ getDepartment(getProjectOwner(project)) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 space-y-2 border-t border-gray-100 pt-4 text-sm text-gray-600">
                                <p>
                                    <span class="font-semibold text-gray-800">Semester:</span>
                                    {{ project.semester ?? 'N/A' }}
                                </p>

                                <p>
                                    <span class="font-semibold text-gray-800">Adviser:</span>
                                    {{ getAdviserName(project) }}
                                </p>
                            </div>

                            <div class="mt-auto pt-6">
                              <div class="flex items-center justify-end border-t border-gray-100 pt-4">
    <button
        type="button"
        @click="goToManageProject(project)"
        class="inline-flex min-w-[140px] items-center justify-center rounded-md bg-[#0C4B05] px-4 py-2.5 text-sm font-medium text-white transition hover:opacity-90"
    >
        Manage Project
    </button>
</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </SidebarInset>
    </SidebarProvider>
</template>