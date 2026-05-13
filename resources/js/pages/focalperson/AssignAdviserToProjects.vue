<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { Head, router } from '@inertiajs/vue3';
import { FolderOpen } from 'lucide-vue-next';
import { computed } from 'vue';

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
    first_name?: string | null;
    last_name?: string | null;
    name?: string | null;
    department?: Department | null;
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

    // ✅ add these
    proposal_titles?: string[];
    approved_proposal_index?: number | null;

    abstract?: string | null;
    project_type?: string | null;
    academic_year?: string | null;
    semester?: string | null;
    status?: string | null;
    adviser_status?: string | null;
    adviser_id?: number | null;
    adviser?: Adviser | null;
    schedule?: Schedule | null;
    student?: Person | null;
    creator?: Person | null;
    user?: Person | null;
    leader?: Person | null;
    researchers?: Person[];
};

const props = defineProps<{
    projects: Project[];
}>();

const ongoingProjectsWithAdviser = computed(() => {
    return props.projects.filter((project) => {
        const status = String(project.status ?? '').toLowerCase();

        return Boolean(project.adviser_id || project.adviser) && status === 'ongoing';
    });
});

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
    if (!project.adviser) return 'No adviser assigned';

    if (project.adviser.name) return project.adviser.name;

    const fullName = `${project.adviser.first_name ?? ''} ${project.adviser.last_name ?? ''}`.trim();

    return fullName || 'No adviser assigned';
};

const getDisplayStatus = (project: Project): string => {
    const status = project.status ?? 'Ongoing';

    return String(status).toLowerCase() === 'ongoing' ? 'Ongoing' : status;
};

// ✅ display only accepted topic
const getAcceptedTopic = (project: Project): string => {
    const titles = project.proposal_titles ?? [];
    const approvedIndex = project.approved_proposal_index;

    if (
        approvedIndex !== null &&
        approvedIndex !== undefined &&
        titles[approvedIndex]
    ) {
        return titles[approvedIndex];
    }

    return project.title;
};
</script>

<template>
    <Head title="Ongoing Projects" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-4 md:px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem>Ongoing Projects</CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <main class="space-y-5 p-4 md:p-6">
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-5 shadow-sm md:px-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 md:text-2xl">
                                Ongoing Projects
                            </h1>

                            <p class="mt-1 text-sm text-gray-500">
                                These are ongoing projects with assigned advisers.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="!ongoingProjectsWithAdviser.length"
                    class="flex min-h-[60vh] items-center justify-center"
                >
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FolderOpen />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Ongoing Projects Found</EmptyTitle>

                        <EmptyDescription>
                            Ongoing projects will appear here once the project status is set to ongoing.
                        </EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3"
                >
                    <div
                        v-for="project in ongoingProjectsWithAdviser"
                        :key="project.id"
                        class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div
                            class="min-h-[128px] border-b border-gray-100 bg-gradient-to-r from-[#0C4B05] to-[#146b0c] px-5 py-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p
                                        class="mb-2 text-xs font-semibold uppercase tracking-wider text-white/80"
                                    >
                                        {{ project.project_type ?? 'No project type' }}
                                    </p>

                                    <!-- ✅ accepted topic only -->
                                    <h2
                                        class="line-clamp-3 text-lg font-bold leading-snug text-white"
                                    >
                                        {{ getAcceptedTopic(project) }}
                                    </h2>
                                </div>

                                <span
                                    class="shrink-0 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white"
                                >
                                    {{ getDisplayStatus(project) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <div>
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-gray-400"
                                >
                                    Project Leader
                                </p>

                                <div class="mt-3 flex items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#0C4B05]/10 text-sm font-bold uppercase text-[#0C4B05]"
                                    >
                                        {{ getInitials(getProjectOwner(project)) }}
                                    </div>

                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-base font-semibold text-gray-900"
                                        >
                                            {{ getFullName(getProjectOwner(project)) }}
                                        </p>

                                        <p class="truncate text-sm text-gray-500">
                                            {{ getDepartment(getProjectOwner(project)) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-5 space-y-2 border-t border-gray-100 pt-4 text-sm text-gray-600"
                            >
                                <p>
                                    <span class="font-semibold text-gray-800">
                                        Adviser:
                                    </span>

                                    {{ getAdviserName(project) }}
                                </p>
                            </div>

                            <div class="mt-auto pt-6">
                                <div
                                    class="flex items-center justify-end border-t border-gray-100 pt-4"
                                >
                                    <button
                                        type="button"
                                        @click="goToManageProject(project)"
                                        class="inline-flex min-w-[140px] items-center justify-center rounded-md bg-[#0C4B05] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#0a3d04]"
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