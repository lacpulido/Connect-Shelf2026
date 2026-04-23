<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import ConfirmScheduleModal from '@/components/ConfirmScheduleModal.vue';
import RescheduleScheduleModal from '@/components/RescheduleScheduleModal.vue';
import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { useDisplayFormatters } from '@/composables/useDisplayFormatter';
import { Head, router } from '@inertiajs/vue3';
import { Calendar1 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface PanelMember {
    name: string;
    department: string;
}

interface AssignedSchedule {
    id: number;
    project_title: string;
    created_by: string;
    description: string;
    department: string;
    defense_date: string | null;
    defense_time: string | null;
    venue: string | null;
    role: string;
    project_type?: string;
    status?: string | null;
    is_confirmed?: boolean | number;
    reschedule_requested?: boolean | number;
    panel_members?: PanelMember[];
    has_schedule?: boolean;
}

const props = defineProps<{
    schedules: AssignedSchedule[];
}>();

const { formatDate, formatDefenseTime } = useDisplayFormatters();

const schedulesList = computed(() => props.schedules ?? []);

const showRescheduleModal = ref(false);
const showConfirmModal = ref(false);
const selectedSchedule = ref<AssignedSchedule | null>(null);

const pollingInterval = 5000;
let poller: ReturnType<typeof setInterval> | null = null;

const reloadSchedules = () => {
    router.reload({
        only: ['schedules'],
        preserveScroll: true,
        preserveState: false,
    });
};

const startPolling = () => {
    if (poller) return;

    poller = setInterval(() => {
        if (document.hidden) return;
        reloadSchedules();
    }, pollingInterval);
};

const stopPolling = () => {
    if (poller) {
        clearInterval(poller);
        poller = null;
    }
};

onMounted(() => {
    startPolling();
});

onBeforeUnmount(() => {
    stopPolling();
});

const openRescheduleModal = (schedule: AssignedSchedule) => {
    if (!schedule.has_schedule) return;
    if (isConfirmed(schedule)) return;
    if (isRescheduleRequested(schedule)) return;

    selectedSchedule.value = schedule;
    showRescheduleModal.value = true;
};

const openConfirmModal = (schedule: AssignedSchedule) => {
    if (!schedule.has_schedule) return;
    if (isConfirmed(schedule)) return;
    if (isRescheduleRequested(schedule)) return;

    selectedSchedule.value = schedule;
    showConfirmModal.value = true;
};

const closeRescheduleModal = () => {
    showRescheduleModal.value = false;
    selectedSchedule.value = null;
};

const closeConfirmModal = () => {
    showConfirmModal.value = false;
    selectedSchedule.value = null;
};

const handleConfirmed = () => {
    closeConfirmModal();
    reloadSchedules();
};

const handleRescheduleSubmitted = () => {
    closeRescheduleModal();
    reloadSchedules();
};

const getScheduleTypeLabel = (schedule: AssignedSchedule) => {
    return schedule.project_type || 'Defense Schedule';
};

const normalizedStatus = (schedule: AssignedSchedule) => {
    return String(schedule.status ?? '')
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, '_');
};

const isTruthy = (value: unknown) => {
    return value === true || value === 1 || value === '1';
};

const isConfirmed = (schedule: AssignedSchedule) => {
    return isTruthy(schedule.is_confirmed) || normalizedStatus(schedule) === 'confirmed';
};

const isRescheduleRequested = (schedule: AssignedSchedule) => {
    return isTruthy(schedule.reschedule_requested) || normalizedStatus(schedule) === 'reschedule_requested';
};
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
                            <p class="mt-1 text-sm text-gray-500">View confirmed defense schedules and assigned projects.</p>
                        </div>
                    </div>
                </div>

                <div v-if="schedulesList.length" class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="schedule in schedulesList"
                        :key="schedule.id"
                        class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div class="min-h-[92px] border-b border-gray-100 bg-gradient-to-r from-[#0C4B05] to-[#146b0c] px-5 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-white/80">
                                        {{ getScheduleTypeLabel(schedule) }}
                                    </p>

                                    <div class="min-h-[40px]">
                                        <h2 class="line-clamp-2 text-[16px] font-semibold leading-snug text-white">
                                            {{ schedule.project_title }}
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-5">
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
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm font-medium text-gray-500">Your Role</span>
                                    <span class="text-right text-sm font-semibold text-gray-900">
                                        {{ schedule.role || 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="schedule.panel_members?.length" class="mt-5 border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Panel Members</p>

                                <ul class="mt-3 space-y-1">
                                    <li v-for="(member, index) in schedule.panel_members" :key="index" class="text-sm text-gray-700">
                                        <span class="font-semibold text-gray-900">
                                            {{ member.name }}
                                        </span>
                                        <span class="text-gray-500"> — {{ member.department }} </span>
                                    </li>
                                </ul>
                            </div>

                            <div class="mt-auto pt-5">
                                <div class="border-t border-gray-100 pt-4">
                                    <template v-if="schedule.has_schedule">
                                        <div class="grid min-h-[44px] grid-cols-2 gap-2">
                                            <template v-if="!isConfirmed(schedule) && !isRescheduleRequested(schedule)">
                                                <button
                                                    type="button"
                                                    class="flex h-11 w-full items-center justify-center rounded-md border border-amber-400 bg-amber-50 px-4 text-sm font-medium text-amber-700 transition hover:bg-amber-100 focus:ring-2 focus:ring-amber-200"
                                                    @click="openRescheduleModal(schedule)"
                                                >
                                                    Reschedule
                                                </button>

                                                <button
                                                    type="button"
                                                    class="flex h-11 w-full items-center justify-center rounded-md bg-[#0C4B05] px-4 text-sm font-medium text-white transition hover:opacity-90"
                                                    @click="openConfirmModal(schedule)"
                                                >
                                                    Confirm
                                                </button>
                                            </template>

                                            <template v-else-if="isRescheduleRequested(schedule)">
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="col-span-2 flex h-11 w-full cursor-not-allowed items-center justify-center rounded-md border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 opacity-90 focus:ring-gray-300"
                                                >
                                                    Reschedule Requested
                                                </button>
                                            </template>

                                            <template v-else-if="isConfirmed(schedule)">
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="col-span-2 flex h-11 w-full cursor-not-allowed items-center justify-center rounded-md bg-[#0C4B05] px-4 text-sm font-medium text-white opacity-80"
                                                >
                                                    Confirmed
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    <template v-else>
                                        <div class="grid min-h-[44px] grid-cols-2 gap-2">
                                            <button
                                                type="button"
                                                disabled
                                                class="col-span-2 flex h-11 w-full cursor-not-allowed items-center justify-center rounded-md bg-gray-400 px-4 text-sm font-medium text-white opacity-80"
                                            >
                                                Waiting for Schedule
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="flex min-h-[60vh] items-center justify-center">
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Calendar1 />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Schedules Available</EmptyTitle>

                        <EmptyDescription> Schedules for Defense will appear here. </EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>
            </div>

            <RescheduleScheduleModal
                :show="showRescheduleModal"
                :schedule="selectedSchedule"
                @close="closeRescheduleModal"
                @submitted="handleRescheduleSubmitted"
            />

            <ConfirmScheduleModal :show="showConfirmModal" :schedule="selectedSchedule" @close="closeConfirmModal" @confirmed="handleConfirmed" />
        </SidebarInset>
    </SidebarProvider>
</template>
