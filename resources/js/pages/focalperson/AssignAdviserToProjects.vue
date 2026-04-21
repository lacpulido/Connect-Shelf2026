<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import AssignPanelist from '@/components/AssignPanelist.vue'
import ProjectDetailsModal from '@/components/ProjectDetailsModal.vue'
import SetDefenseSchedule from '@/components/SetDefenseSchedule.vue'
import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import type { User } from '@/types/user'
import { Head, usePage, usePoll } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

type Department = {
    id: number
    name: string
}

type ModalFaculty = Omit<User, 'email' | 'roles'> & {
    email: string
    roles?: unknown[]
}

type ProjectSchedule = {
    id?: number
    status?: string | null
    is_confirmed?: boolean | number | null
    confirmation_status?: string | null
    reschedule_requested?: boolean | number | null
    is_reschedule_requested?: boolean | number | null
    request_reschedule?: boolean | number | null
    reschedule_status?: string | null
    defense_date?: string | null
    defense_time?: string | null
    venue?: string | null
} | null

type Project = {
    id: number
    title: string
    description?: string
    user?: User | null
    researchers?: User[]
    schedule?: ProjectSchedule
    panelists?: ModalFaculty[]
    status?: string
    project_type?: string
}

type Filters = Record<string, unknown>

const page = usePage()

const projects = ref<Project[]>([...((page.props.projects as Project[]) || [])])

watch(
    () => page.props.projects,
    (newProjects) => {
        projects.value = [...((newProjects as Project[]) || [])]
    },
    { deep: true },
)

const hasProjects = computed(() => projects.value.length > 0)
const departments = computed<Department[]>(() => (page.props.departments as Department[]) || [])
const filters = computed<Filters>(() => (page.props.filters as Filters) || {})

const faculties = computed<ModalFaculty[]>(() => {
    return (((page.props.faculties as User[]) || [])
        .filter((faculty: any) => {
            return !faculty.roles?.some((role: any) => role.name === 'Administrator')
        })
        .map((faculty: any) => ({
            ...faculty,
            email: faculty.email ?? '',
            roles: faculty.roles,
        }))) as ModalFaculty[]
})

const showDetailsModal = ref(false)
const showScheduleModal = ref(false)
const showPanelistModal = ref(false)
const selectedProject = ref<Project | null>(null)
const pollingProjectId = ref<number | null>(null)

const { start: startPolling, stop: stopPolling } = usePoll(
    1500,
    {
        only: ['projects'],
        onFinish: () => {
            if (!pollingProjectId.value) return

            const updatedProject = projects.value.find((project) => project.id === pollingProjectId.value)

            if (updatedProject?.schedule) {
                stopPolling()
                pollingProjectId.value = null

                if (selectedProject.value?.id === updatedProject.id) {
                    selectedProject.value = updatedProject
                }
            }
        },
    },
    { autoStart: false },
)

const openDetailsModal = (project: Project) => {
    selectedProject.value = project
    showDetailsModal.value = true
}

const openScheduleModal = (project: Project) => {
    selectedProject.value = project
    showScheduleModal.value = true
}

const openPanelistModal = (project: Project) => {
    selectedProject.value = project
    showPanelistModal.value = true
}

const handlePanelistAdded = (faculty: ModalFaculty) => {
    if (!selectedProject.value) return

    const index = projects.value.findIndex((project) => project.id === selectedProject.value?.id)
    if (index === -1) return

    const existingPanelists = projects.value[index].panelists || []
    const alreadyExists = existingPanelists.some((panelist) => panelist.id === faculty.id)

    if (!alreadyExists) {
        projects.value[index] = {
            ...projects.value[index],
            panelists: [...existingPanelists, faculty],
        }

        selectedProject.value = projects.value[index]

        if ((projects.value[index].panelists?.length || 0) >= 3) {
            showPanelistModal.value = false
        }
    }
}

const handleScheduleSaved = (projectId: number) => {
    pollingProjectId.value = projectId
    startPolling()
}

const getDisplayStatus = (status?: string) => status || 'Ongoing'

const isScheduleConfirmed = (project: Project) => {
    const schedule = project.schedule
    if (!schedule) return false

    const normalizedStatus = String(schedule.status || schedule.confirmation_status || '')
        .toLowerCase()
        .trim()

    return schedule.is_confirmed === true || schedule.is_confirmed === 1 || normalizedStatus === 'confirmed'
}

const getPanelistCount = (project: Project) => Array.isArray(project.panelists) ? project.panelists.length : 0
const hasReachedMaxPanelists = (project: Project) => getPanelistCount(project) >= 3
const arePanelistsConfirmed = (project: Project) => getPanelistCount(project) >= 3
const canViewDetails = (project: Project) => isScheduleConfirmed(project) && arePanelistsConfirmed(project)
const hasSchedule = (project: Project) => !!project.schedule

const hasRescheduleRequest = (project: Project) => {
    const schedule = project.schedule
    if (!schedule) return false

    const statuses = [schedule.status, schedule.confirmation_status, schedule.reschedule_status]
        .filter(Boolean)
        .map((value) => String(value).toLowerCase().trim())

    return (
        schedule.reschedule_requested === true ||
        schedule.reschedule_requested === 1 ||
        schedule.is_reschedule_requested === true ||
        schedule.is_reschedule_requested === 1 ||
        schedule.request_reschedule === true ||
        schedule.request_reschedule === 1 ||
        statuses.includes('reschedule_requested') ||
        statuses.includes('for_reschedule') ||
        statuses.includes('requested_reschedule') ||
        statuses.includes('reschedule')
    )
}

const shouldShowScheduleButton = (project: Project) => !hasSchedule(project) || hasRescheduleRequest(project)
const getScheduleButtonLabel = (project: Project) => hasRescheduleRequest(project) ? 'Reschedule' : 'Set Schedule'
const shouldShowAssignPanelistButton = (project: Project) => !hasReachedMaxPanelists(project)

const getFullName = (user?: User | null) => {
    if (!user) return 'Not assigned'
    return `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim() || 'Not assigned'
}

const getInitials = (user?: User | null) => {
    if (!user) return 'NA'
    return `${user.first_name?.[0] ?? ''}${user.last_name?.[0] ?? ''}`.trim().toUpperCase() || 'NA'
}

const getProjectTypeLabel = (project: Project) => project.project_type || 'Project'

const baseButtonClass =
    'inline-flex h-10 w-full items-center justify-center rounded-lg px-2.5 py-2 text-[13px] font-medium transition hover:opacity-90 focus:outline-none focus:ring-2'

const primaryButtonClass =
    `${baseButtonClass} bg-[#0C4B05] text-white focus:ring-[#0C4B05]/30`

const secondaryButtonClass =
    `${baseButtonClass} border border-gray-300 bg-white text-gray-700 focus:ring-gray-300`

const getScheduleButtonClass = (project: Project) =>
    hasRescheduleRequest(project)
        ? `${baseButtonClass} border border-amber-300 bg-amber-50 text-amber-700 focus:ring-amber-200`
        : primaryButtonClass
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

            <div class="space-y-5 p-6">
                <div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
                            <p class="mt-1 text-sm text-gray-500">
                                Manage, review, and schedule all submitted projects.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="hasProjects" class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                    <div
                        v-for="project in projects"
                        :key="project.id"
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div class="border-b border-gray-100 bg-gradient-to-r from-[#0C4B05] to-[#146b0c] px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-white/80">
                                        {{ getProjectTypeLabel(project) }}
                                    </p>
                                    <h2 class="line-clamp-2 text-lg font-bold leading-snug text-white">
                                        {{ project.title }}
                                    </h2>
                                </div>

                                <span
                                    :class="[
                                        'inline-flex shrink-0 rounded-full px-3 py-1 text-xs font-semibold',
                                        project.status?.toLowerCase() === 'completed'
                                            ? 'border border-white/20 bg-white/20 text-white'
                                            : project.status?.toLowerCase() === 'pending'
                                              ? 'border border-yellow-200 bg-yellow-100 text-yellow-800'
                                              : project.status?.toLowerCase() === 'scheduled'
                                                ? 'border border-blue-200 bg-blue-100 text-blue-700'
                                                : project.status?.toLowerCase() === 'cancelled'
                                                  ? 'border border-red-200 bg-red-100 text-red-700'
                                                  : 'border border-white/15 bg-white/15 text-white',
                                    ]"
                                >
                                    {{ getDisplayStatus(project.status) }}
                                </span>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="mb-5 border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Leader</p>

                                <div class="mt-3 flex items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#0C4B05]/10 text-sm font-bold uppercase text-[#0C4B05]"
                                    >
                                        {{ getInitials(project.user) }}
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-base font-semibold text-gray-900">
                                            {{ getFullName(project.user) }}
                                        </p>
                                        <p class="truncate text-sm text-gray-500">Student Leader</p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="project.status?.toLowerCase() !== 'completed'"
                                class="border-t border-gray-100 pt-4"
                            >
                                <div v-if="canViewDetails(project)" class="space-y-3">
                                    <div
                                        v-if="shouldShowScheduleButton(project)"
                                        class="grid grid-cols-2 gap-2.5"
                                    >
                                        <button
                                            type="button"
                                            @click="openScheduleModal(project)"
                                            :class="getScheduleButtonClass(project)"
                                        >
                                            {{ getScheduleButtonLabel(project) }}
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDetailsModal(project)"
                                            :class="secondaryButtonClass"
                                        >
                                            View Details
                                        </button>
                                    </div>

                                    <div v-else class="grid grid-cols-1">
                                        <button
                                            type="button"
                                            @click="openDetailsModal(project)"
                                            :class="secondaryButtonClass"
                                        >
                                            View Details
                                        </button>
                                    </div>

                                    <button
                                        v-if="shouldShowAssignPanelistButton(project)"
                                        type="button"
                                        @click="openPanelistModal(project)"
                                        :class="secondaryButtonClass"
                                    >
                                        Assign Panelist
                                    </button>
                                </div>

                                <div v-else>
                                    <div
                                        :class="[
                                            'grid gap-2.5',
                                            shouldShowScheduleButton(project) && shouldShowAssignPanelistButton(project)
                                                ? 'grid-cols-2'
                                                : 'grid-cols-1',
                                        ]"
                                    >
                                        <button
                                            v-if="shouldShowScheduleButton(project)"
                                            type="button"
                                            @click="openScheduleModal(project)"
                                            :class="getScheduleButtonClass(project)"
                                        >
                                            {{ getScheduleButtonLabel(project) }}
                                        </button>

                                        <button
                                            v-if="shouldShowAssignPanelistButton(project)"
                                            type="button"
                                            @click="openPanelistModal(project)"
                                            :class="secondaryButtonClass"
                                        >
                                            Assign Panelist
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="border-t border-gray-100 pt-4">
                                <div
                                    class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-center text-sm font-semibold text-green-700"
                                >
                                    This project is already completed.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="flex min-h-[60vh] items-center justify-center">
                    <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white px-8 py-12 text-center shadow-sm">
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#0C4B05]/10 text-[#0C4B05]"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-8 w-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 7.5A2.5 2.5 0 015.5 5h13A2.5 2.5 0 0121 7.5v9A2.5 2.5 0 0118.5 19h-13A2.5 2.5 0 013 16.5v-9z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 10h8M8 14h5"
                                />
                            </svg>
                        </div>

                        <h2 class="text-xl font-semibold text-gray-900">No Projects Yet</h2>
                        <p class="mt-2 text-sm text-gray-500">
                            There are no submitted projects at the moment.
                        </p>
                    </div>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>

    <ProjectDetailsModal
        :show="showDetailsModal"
        :project="selectedProject"
        :faculties="faculties"
        @close="showDetailsModal = false"
    />

    <AssignPanelist
        v-if="selectedProject"
        :show="showPanelistModal"
        :project="selectedProject"
        :faculties="faculties"
        :departments="departments"
        :filters="filters"
        @added="handlePanelistAdded"
        @close="showPanelistModal = false"
    />

    <SetDefenseSchedule
        :show="showScheduleModal"
        :project="selectedProject"
        @saved="handleScheduleSaved"
        @close="showScheduleModal = false"
    />
</template>