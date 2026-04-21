<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import ConfirmScheduleModal from '@/components/ConfirmScheduleModal.vue'
import RescheduleScheduleModal from '@/components/RescheduleScheduleModal.vue'
import { Breadcrumb, BreadcrumbItem as CrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { useDisplayFormatters } from '@/composables/useDisplayFormatter'
import { useScheduleUi } from '@/composables/useScheduleUi'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { Calendar1, FolderOpen } from 'lucide-vue-next'
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty'

interface PanelMember {
    name: string
    department: string
}

interface AssignedSchedule {
    id: number
    project_title: string
    created_by: string
    description: string
    department: string
    defense_date: string | null
    defense_time: string | null
    venue: string | null
    role: string
    project_type?: string
    status?: string | null
    is_confirmed?: boolean
    reschedule_requested?: boolean
    panel_members?: PanelMember[]
    has_schedule?: boolean
}

const props = defineProps<{
    schedules: AssignedSchedule[]
}>()

const { formatDate, formatDefenseTime } = useDisplayFormatters()
const { normalizeStatus } = useScheduleUi()

const schedulesList = computed(() => props.schedules ?? [])

const showRescheduleModal = ref(false)
const showConfirmModal = ref(false)
const selectedSchedule = ref<AssignedSchedule | null>(null)

const pollingInterval = 5000
let poller: ReturnType<typeof setInterval> | null = null

const reloadSchedules = () => {
    router.reload({
        only: ['schedules'],
    })
}

const startPolling = () => {
    if (poller) return

    poller = setInterval(() => {
        if (document.hidden) return
        reloadSchedules()
    }, pollingInterval)
}

const stopPolling = () => {
    if (poller) {
        clearInterval(poller)
        poller = null
    }
}

onMounted(() => {
    startPolling()
})

onBeforeUnmount(() => {
    stopPolling()
})

const openRescheduleModal = (schedule: AssignedSchedule) => {
    if (!schedule.has_schedule) return
    selectedSchedule.value = schedule
    showRescheduleModal.value = true
}

const openConfirmModal = (schedule: AssignedSchedule) => {
    if (!schedule.has_schedule) return
    if (isRescheduleRequested(schedule)) return
    selectedSchedule.value = schedule
    showConfirmModal.value = true
}

const closeRescheduleModal = () => {
    showRescheduleModal.value = false
    selectedSchedule.value = null
}

const closeConfirmModal = () => {
    showConfirmModal.value = false
    selectedSchedule.value = null
}

const handleConfirmed = () => {
    closeConfirmModal()
    reloadSchedules()
}

const getScheduleTypeLabel = (schedule: AssignedSchedule) => {
    return schedule.project_type || 'Defense Schedule'
}

const resolveScheduleStatus = (schedule: AssignedSchedule) => {
    if (schedule.status) return schedule.status
    if (schedule.is_confirmed) return 'confirmed'
    if (schedule.reschedule_requested) return 'reschedule_requested'
    return 'pending'
}

const isConfirmed = (schedule: AssignedSchedule) => {
    return normalizeStatus(resolveScheduleStatus(schedule)) === 'confirmed'
}

const isRescheduleRequested = (schedule: AssignedSchedule) => {
    return normalizeStatus(resolveScheduleStatus(schedule)) === 'reschedule_requested'
}
</script>

<template>
    <Head title="Assigned Schedules" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem>Schedules</CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Assigned Schedules</h1>
                            <p class="mt-1 text-sm text-gray-500">
                                View confirmed defense schedules and assigned projects.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="schedulesList.length"
                    class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <div
                        v-for="schedule in schedulesList"
                        :key="schedule.id"
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div class="border-b border-gray-100 bg-gradient-to-r from-[#0C4B05] to-[#146b0c] px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-white/80">
                                        {{ getScheduleTypeLabel(schedule) }}
                                    </p>
                                    <h2 class="line-clamp-2 text-lg font-bold leading-snug text-white">
                                        {{ schedule.project_title }}
                                    </h2>
                                </div>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="space-y-3 border-t border-gray-100 pt-4">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm font-medium text-gray-500">Date</span>
                                    <span class="text-right text-sm font-semibold text-gray-900">
                                        {{ formatDate(schedule.defense_date, 'Not yet scheduled') }}
                                    </span>
                                </div>

                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm font-medium text-gray-500">Time</span>
                                    <span class="text-right text-sm font-semibold text-gray-900">
                                        {{ formatDefenseTime(schedule.defense_time, 'TBA') }}
                                    </span>
                                </div>

                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm font-medium text-gray-500">Venue</span>
                                    <span class="text-right text-sm font-semibold text-gray-900">
                                        {{ schedule.venue || 'TBA' }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="schedule.panel_members?.length"
                                class="mt-5 border-t border-gray-100 pt-4"
                            >
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    Panel Members
                                </p>

                                <ul class="mt-3 space-y-1">
                                    <li
                                        v-for="(member, index) in schedule.panel_members"
                                        :key="index"
                                        class="text-sm text-gray-700"
                                    >
                                        <span class="font-semibold text-gray-900">
                                            {{ member.name }}
                                        </span>
                                        <span class="text-gray-500">
                                            — {{ member.department }}
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <div class="mt-5 border-t border-gray-100 pt-4">
                                <template v-if="schedule.has_schedule">
                                    <div
                                        :class="
                                            isConfirmed(schedule) || isRescheduleRequested(schedule)
                                                ? 'grid grid-cols-1 gap-2'
                                                : 'grid grid-cols-2 gap-2'
                                        "
                                    >
                                        <template
                                            v-if="!isConfirmed(schedule) && !isRescheduleRequested(schedule)"
                                        >
                                            <button
                                                type="button"
                                                class="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:opacity-90"
                                                @click="openRescheduleModal(schedule)"
                                            >
                                                Reschedule
                                            </button>

                                            <button
                                                type="button"
                                                class="rounded-md bg-[#0C4B05] px-4 py-2 text-sm text-white hover:opacity-90"
                                                @click="openConfirmModal(schedule)"
                                            >
                                                Confirm
                                            </button>
                                        </template>

                                        <template v-else-if="isRescheduleRequested(schedule)">
                                            <button
                                                type="button"
                                                disabled
                                                class="cursor-not-allowed rounded-md bg-gray-400 px-4 py-2 text-sm text-white opacity-80"
                                            >
                                                Requested
                                            </button>
                                        </template>

                                        <template v-else>
                                            <button
                                                type="button"
                                                disabled
                                                class="cursor-not-allowed rounded-md bg-[#0C4B05] px-4 py-2 text-sm text-white opacity-70"
                                            >
                                                Confirmed
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                <template v-else>
                                    <button
                                        type="button"
                                        disabled
                                        class="w-full cursor-not-allowed rounded-md bg-gray-400 px-4 py-2 text-sm text-white opacity-80"
                                    >
                                        Waiting for Schedule
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Calendar1/>
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Schedules Available</EmptyTitle>

                        <EmptyDescription>
                           Schedules for Defense will appear here.
                        </EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>
            </div>

            <RescheduleScheduleModal
                :show="showRescheduleModal"
                :schedule="selectedSchedule"
                @close="closeRescheduleModal"
            />

            <ConfirmScheduleModal
                :show="showConfirmModal"
                :schedule="selectedSchedule"
                @close="closeConfirmModal"
                @confirmed="handleConfirmed"
            />
        </SidebarInset>
    </SidebarProvider>
</template>