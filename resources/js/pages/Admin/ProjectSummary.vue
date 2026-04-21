<script setup lang="ts">
import AppSidebar from '@/components/AdminAppSider.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { computed, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'

type Department = {
    id: number
    name: string
}

type ProjectSchedule = {
    date: string | null
    start_time: string | null
    end_time: string | null
    venue: string | null
}

type Project = {
    id: number
    title: string
    description: string | null
    department: string
    department_id: number | null
    owner: string
    adviser: string
    schedule: ProjectSchedule | null
}

type DepartmentSummary = {
    id: number
    name: string
    total_projects: number
    projects: Project[]
}

const props = defineProps<{
    filters: {
        search: string
        department: string | number | null
    }
    departments: Department[]
    departmentSummaries: DepartmentSummary[]
    projects: Project[]
    stats: {
        total_projects: number
        total_departments_with_projects: number
    }
}>()

const form = reactive({
    search: props.filters.search ?? '',
    department: props.filters.department ?? '',
})

const applyFilters = () => {
    router.get(
        route('admin.reports.project-summary'),
        {
            search: form.search || undefined,
            department: form.department || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    )
}

const resetFilters = () => {
    form.search = ''
    form.department = ''
    applyFilters()
}

watch(
    () => form.department,
    () => {
        applyFilters()
    },
)

let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch(
    () => form.search,
    () => {
        if (searchTimeout) clearTimeout(searchTimeout)

        searchTimeout = setTimeout(() => {
            applyFilters()
        }, 400)
    },
)

const groupedProjects = computed(() => {
    return props.departmentSummaries.filter((item) => item.total_projects > 0)
})

const emptyStateMessage = computed(() => {
    if (form.search || form.department) {
        return 'No projects found for the current filters.'
    }

    return 'No projects available yet.'
})

const formatSchedule = (schedule: ProjectSchedule | null) => {
    if (!schedule || !schedule.date) return 'No schedule'

    const timePart =
        schedule.start_time && schedule.end_time
            ? `${schedule.start_time} - ${schedule.end_time}`
            : schedule.start_time || schedule.end_time || ''

    const venuePart = schedule.venue ? ` • ${schedule.venue}` : ''

    return `${schedule.date}${timePart ? ` • ${timePart}` : ''}${venuePart}`
}
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
                        <BreadcrumbItem class="font-semibold">Project Summary</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <!-- Header / Intro -->
                <div class="rounded-xl border bg-muted/40 p-4">
                    <h1 class="text-2xl font-bold tracking-tight">Project Summary</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        View project totals by department and search project-related records.
                    </p>
                </div>

                <!-- Stats -->
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">Total Projects</p>
                        <p class="mt-2 text-3xl font-bold">{{ stats.total_projects }}</p>
                    </div>

                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">Departments with Projects</p>
                        <p class="mt-2 text-3xl font-bold">
                            {{ stats.total_departments_with_projects }}
                        </p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="rounded-xl border bg-muted/40 p-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Search Project
                            </label>
                            <input
                                v-model="form.search"
                                type="text"
                                placeholder="Search by title, description, owner, or adviser..."
                                class="w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                            />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Department
                            </label>
                            <select
                                v-model="form.department"
                                class="w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                            >
                                <option value="">All Departments</option>
                                <option
                                    v-for="department in departments"
                                    :key="department.id"
                                    :value="department.id"
                                >
                                    {{ department.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="rounded-md border bg-white px-4 py-2 text-sm font-medium transition hover:bg-gray-50"
                        >
                            Reset Filters
                        </button>
                    </div>
                </div>

                <!-- Projects per Department -->
                <div class="overflow-hidden rounded-xl border bg-white">
                    <div class="border-b bg-muted/50 px-4 py-3">
                        <h2 class="text-base font-semibold">Projects per Department</h2>
                        <p class="text-sm text-gray-500">
                            Quick overview of how many projects belong to each department.
                        </p>
                    </div>

                    <div v-if="groupedProjects.length" class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="department in groupedProjects"
                            :key="department.id"
                            class="rounded-xl border bg-gray-50 p-4"
                        >
                            <div>
                                <h3 class="text-base font-semibold">{{ department.name }}</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Total Projects: {{ department.total_projects }}
                                </p>
                            </div>

                            <div class="mt-4 space-y-2">
                                <div
                                    v-for="project in department.projects"
                                    :key="project.id"
                                    class="rounded-lg border bg-white p-3"
                                >
                                    <p class="font-medium">{{ project.title }}</p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Owner: {{ project.owner }}
                                    </p>
                                </div>

                                <p
                                    v-if="department.projects.length === 0"
                                    class="text-sm text-gray-500"
                                >
                                    No projects for this department.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-6 text-center text-sm text-gray-500">
                        {{ emptyStateMessage }}
                    </div>
                </div>

                <!-- Detailed Project List -->
                <div class="overflow-hidden rounded-xl border bg-white">
                    <div class="border-b bg-muted/50 px-4 py-3">
                        <h2 class="text-base font-semibold">Detailed Project List by Department</h2>
                        <p class="text-sm text-gray-500">
                            Grouped records of projects under each department.
                        </p>
                    </div>

                    <div v-if="groupedProjects.length" class="space-y-6 p-4">
                        <div
                            v-for="department in groupedProjects"
                            :key="department.id"
                            class="overflow-hidden rounded-xl border"
                        >
                            <div class="border-b bg-gray-50 px-4 py-3">
                                <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                                    <h3 class="text-base font-semibold">{{ department.name }}</h3>
                                    <span class="text-sm text-gray-500">
                                        {{ department.total_projects }} project(s)
                                    </span>
                                </div>
                            </div>

                            <div class="divide-y">
                                <div
                                    v-for="project in projects.filter((p) => p.department_id === department.id)"
                                    :key="project.id"
                                    class="p-4"
                                >
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-base font-semibold">{{ project.title }}</h4>

                                            <p
                                                v-if="project.description"
                                                class="mt-1 text-sm text-gray-500"
                                            >
                                                {{ project.description }}
                                            </p>

                                            <div class="mt-3 grid gap-2 text-sm text-gray-600 md:grid-cols-2">
                                                <p>
                                                    <span class="font-medium text-gray-900">Owner:</span>
                                                    {{ project.owner }}
                                                </p>
                                                <p>
                                                    <span class="font-medium text-gray-900">Adviser:</span>
                                                    {{ project.adviser }}
                                                </p>
                                                <p class="md:col-span-2">
                                                    <span class="font-medium text-gray-900">Schedule:</span>
                                                    {{ formatSchedule(project.schedule) }}
                                                </p>
                                            </div>
                                        </div>

                                       <a
    :href="route('admin.projects.show', { project: project.id })"
    class="inline-flex rounded-md border px-4 py-2 text-sm font-medium transition hover:bg-gray-50"
>
    View Project
</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-6 text-center text-sm text-gray-500">
                        {{ emptyStateMessage }}
                    </div>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>