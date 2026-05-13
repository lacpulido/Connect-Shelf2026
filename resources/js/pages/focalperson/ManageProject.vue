<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { useAlerts } from '@/composables/useAlerts';
import { cn } from '@/lib/utils';
import { Head, router } from '@inertiajs/vue3';
import type { DateValue } from '@internationalized/date';
import { DateFormatter, getLocalTimeZone, parseDate, today } from '@internationalized/date';
import { CalendarIcon, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

type ManageTab = 'schedule' | 'panelists' | 'semester';

type Department = {
    id: number;
    name: string;
};

type Faculty = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    department_id?: number | null;
    department?: Department | null;
};

type Schedule = {
    defense_date?: string | null;
    defense_time?: string | null;
    venue?: string | null;
    status?: string | null;
    is_confirmed?: boolean | number | null;
    reschedule_requested?: boolean | number | null;
    requested_defense_date?: string | null;
    requested_defense_time?: string | null;
    reschedule_reason?: string | null;
};

type Project = {
    id: number;
    title: string;
    semester: string;
    status: string;
    adviser?: {
        id: number;
    } | null;
    schedule?: Schedule | null;
    panelists?: Faculty[] | null;
};

const props = defineProps<{
    project: Project;
    faculties: Faculty[];
    departments: Department[];
    filters?: {
        department_id?: number | null;
    };
    requestedDate?: string | null;
    requestedTime?: string | null;
}>();

const { showSuccessAlert, showErrorAlert, showWarningAlert, confirmAction } = useAlerts();

const timeSlots = ['8:00 AM - 11:00 AM', '11:00 AM - 2:00 PM', '2:00 PM - 5:00 PM'];

const validTabs: ManageTab[] = ['schedule', 'panelists', 'semester'];

const tabStorageKey = computed(() => `manage_project_tab_${props.project.id}`);

const isValidTab = (tab: string | null): tab is ManageTab => {
    return !!tab && validTabs.includes(tab as ManageTab);
};

const getInitialTab = (): ManageTab => {
    if (typeof window === 'undefined') return 'schedule';

    const params = new URLSearchParams(window.location.search);
    const urlTab = params.get('tab');

    if (isValidTab(urlTab)) {
        localStorage.setItem(tabStorageKey.value, urlTab);
        return urlTab;
    }

    const savedTab = localStorage.getItem(tabStorageKey.value);

    if (isValidTab(savedTab)) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', savedTab);
        window.history.replaceState({}, '', url.toString());

        return savedTab;
    }

    return 'schedule';
};

const activeTab = ref<ManageTab>(getInitialTab());

const setActiveTab = (tab: ManageTab) => {
    activeTab.value = tab;
};

watch(
    activeTab,
    (tab) => {
        if (typeof window === 'undefined') return;

        localStorage.setItem(tabStorageKey.value, tab);

        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);

        window.history.replaceState({}, '', url.toString());
    },
    { immediate: true },
);

const defenseDate = ref<DateValue>();
const defenseTime = ref<string | null>(null);
const venue = ref('');
const scheduleProcessing = ref(false);

const selectedPanelist = ref<number | null>(null);
const showDropdown = ref(false);
const panelistProcessing = ref(false);
const selectedDepartmentId = ref<number | ''>('');
const errorMessage = ref('');
const localPanelists = ref<Faculty[]>([]);

const semesterProcessing = ref(false);

const defaultPlaceholder = today(getLocalTimeZone());

const df = new DateFormatter('en-US', {
    dateStyle: 'long',
});

const toDateValue = (date?: string | null) => {
    if (!date) return undefined;

    try {
        return parseDate(date);
    } catch {
        return undefined;
    }
};

const toBackendDate = (date?: DateValue) => {
    if (!date) return null;

    return date.toDate(getLocalTimeZone()).toISOString().split('T')[0];
};

const syncScheduleFields = () => {
    const scheduleDate = props.project.schedule?.requested_defense_date
        ? props.project.schedule.requested_defense_date
        : props.project.schedule?.defense_date;

    defenseDate.value = toDateValue(scheduleDate);
    defenseTime.value = props.project.schedule?.requested_defense_time ?? props.project.schedule?.defense_time ?? null;
    venue.value = props.project.schedule?.venue ?? '';
};

watch(
    () => props.project.schedule,
    () => {
        syncScheduleFields();
    },
    { immediate: true, deep: true },
);

watch(
    () => props.project.panelists,
    (panelists) => {
        localPanelists.value = [...(panelists ?? [])];
    },
    { immediate: true, deep: true },
);

const hasSchedule = computed(() => !!props.project.schedule);

const isRescheduleRequested = computed(() => {
    return props.project.schedule?.reschedule_requested === true || props.project.schedule?.reschedule_requested === 1;
});

const isScheduleConfirmed = computed(() => {
    return props.project.schedule?.is_confirmed === true || props.project.schedule?.is_confirmed === 1 || props.project.schedule?.status === 'confirmed';
});

const canEditSchedule = computed(() => {
    return !props.project.schedule || isRescheduleRequested.value;
});

const requestedDate = computed(() => {
    return props.requestedDate ?? props.project.schedule?.requested_defense_date ?? null;
});

const requestedTime = computed(() => {
    return props.requestedTime ?? props.project.schedule?.requested_defense_time ?? null;
});

const scheduleTitle = computed(() => {
    if (!props.project.schedule) return 'Set Schedule';
    if (isRescheduleRequested.value) return 'Update Schedule';
    if (isScheduleConfirmed.value) return 'Confirmed';
    return 'Schedule Set';
});

const scheduleStatusLabel = computed(() => {
    if (!props.project.schedule) return 'No Schedule';
    if (isScheduleConfirmed.value) return 'Schedule Confirmed';
    if (isRescheduleRequested.value) return 'Reschedule Requested';
    return 'Schedule Set';
});

const hasReachedMaximum = computed(() => localPanelists.value.length >= 3);

const availableFaculties = computed(() => {
    return props.faculties.filter((faculty) => {
        const isAlreadyPanelist = localPanelists.value.some((p) => Number(p.id) === Number(faculty.id));
        const isAdviser = Number(props.project.adviser?.id) === Number(faculty.id);
        const facultyDepartmentId = faculty.department_id ?? faculty.department?.id ?? null;

        if (selectedDepartmentId.value === '') {
            return !isAlreadyPanelist && !isAdviser;
        }

        return !isAlreadyPanelist && !isAdviser && Number(facultyDepartmentId) === Number(selectedDepartmentId.value);
    });
});

const selectedPanelistData = computed(() => {
    return props.faculties.find((faculty) => Number(faculty.id) === Number(selectedPanelist.value)) || null;
});

const isFirstSemester = computed(() => {
    return props.project.semester === '1st Semester';
});

const formatDate = (date?: string | null) => {
    if (!date) return 'No date provided';

    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: '2-digit',
    });
};

const goBack = () => {
    router.visit(route('focalperson.projects'));
};

const reloadProject = () => {
    router.reload({
        only: ['project', 'faculties', 'departments', 'requestedDate', 'requestedTime'],
        preserveScroll: true,
        preserveState: true,
    });
};

const submitSchedule = () => {
    if (!canEditSchedule.value) {
        showWarningAlert('Schedule Set', 'You can only update the schedule when a reschedule request is made.');
        return;
    }

    const formattedDefenseDate = toBackendDate(defenseDate.value);

    if (!formattedDefenseDate || !defenseTime.value || !venue.value.trim()) {
        showWarningAlert('Missing fields', 'Please fill in defense date, time, and venue.');
        return;
    }

    scheduleProcessing.value = true;

    router.post(
        route('focalperson.schedule.store'),
        {
            project_id: props.project.id,
            defense_date: formattedDefenseDate,
            defense_time: defenseTime.value,
            venue: venue.value.trim(),
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showSuccessAlert('Schedule Saved', 'The defense schedule has been saved successfully.');
                reloadProject();
            },
            onError: (errors) => {
                const firstError = errors?.schedule || Object.values(errors || {})[0] || 'Unable to save the defense schedule.';

                showErrorAlert('Failed', Array.isArray(firstError) ? String(firstError[0]) : String(firstError));
            },
            onFinish: () => {
                scheduleProcessing.value = false;
            },
        },
    );
};

const selectFaculty = (faculty: Faculty) => {
    if (hasReachedMaximum.value || panelistProcessing.value) return;

    selectedPanelist.value = faculty.id;
    showDropdown.value = false;
    errorMessage.value = '';
};

const assignPanelist = () => {
    if (!selectedPanelist.value || panelistProcessing.value || hasReachedMaximum.value) return;

    const facultyId = selectedPanelist.value;

    panelistProcessing.value = true;
    errorMessage.value = '';

    router.post(
        route('focalperson.panelists.store'),
        {
            project_id: props.project.id,
            faculty_id: facultyId,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['project', 'faculties', 'departments', 'requestedDate', 'requestedTime'],

            onSuccess: () => {
                selectedPanelist.value = null;
                showDropdown.value = false;
                selectedDepartmentId.value = '';
                errorMessage.value = '';

                setActiveTab('panelists');

                showSuccessAlert('Panelist Added', 'The panelist has been assigned successfully.');
            },

            onError: (errors) => {
                const firstError = errors?.faculty_id || Object.values(errors || {})[0] || 'Failed to assign panelist.';

                errorMessage.value = Array.isArray(firstError) ? String(firstError[0]) : String(firstError);

                showErrorAlert('Failed', errorMessage.value);
            },

            onFinish: () => {
                panelistProcessing.value = false;
            },
        },
    );
};

const markFirstSemesterPassed = async () => {
    if (!isFirstSemester.value || semesterProcessing.value) return;

    const result = await confirmAction(
        'Are you sure?',
        'This will move the project to 2nd Semester and reset the schedule. Please confirm before continuing.',
        'Yes, mark as passed',
        'Cancel',
    );

    if (!result.isConfirmed) return;

    semesterProcessing.value = true;

    router.post(
        route('focalperson.projects.mark-first-semester-passed'),
        {
            project_id: props.project.id,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showSuccessAlert('Project Updated', 'Project passed 1st Semester. Semester changed to 2nd Semester and schedule was reset.');

                setActiveTab('schedule');
                reloadProject();
            },
            onError: (errors) => {
                const firstError = errors?.semester || Object.values(errors || {})[0] || 'Unable to update project semester.';

                showErrorAlert('Failed', Array.isArray(firstError) ? String(firstError[0]) : String(firstError));
            },
            onFinish: () => {
                semesterProcessing.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Manage Project" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <!-- Header -->
            <header class="flex h-14 items-center gap-2 border-b px-3 sm:h-16 sm:px-6">
                <SidebarTrigger class="shrink-0" />
                <Separator orientation="vertical" class="h-4 shrink-0" />

                <Breadcrumb class="min-w-0">
                    <BreadcrumbList class="flex items-center gap-1 sm:gap-2">
                        <CrumbItem class="hidden sm:block">Projects</CrumbItem>
                        <ChevronRight class="hidden h-4 w-4 shrink-0 text-gray-400 sm:block" />
                        <CrumbItem class="truncate text-sm">Manage Project</CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <main class="space-y-4 p-3 sm:space-y-5 sm:p-6">
                <!-- Project Title Card -->
                <div class="rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm sm:px-6 sm:py-5">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0">
                            <h1 class="truncate text-xl font-bold text-gray-900 sm:text-2xl">{{ project.title }}</h1>

                            <button
                                @click="goBack"
                                class="mt-3 inline-flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 sm:mt-5"
                            >
                                ← Back to All Projects
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tab Card -->
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-100 px-3 pt-3 sm:px-6 sm:pt-4">
                        <div
                            class="flex gap-1 overflow-x-auto sm:flex-wrap sm:gap-2"
                            style="-webkit-overflow-scrolling: touch; scrollbar-width: none"
                        >
                            <button
                                type="button"
                                @click="activeTab = 'schedule'"
                                :class="[
                                    'shrink-0 whitespace-nowrap rounded-t-xl px-3 py-2.5 text-xs font-semibold transition sm:px-5 sm:py-3 sm:text-sm',
                                    activeTab === 'schedule' ? 'bg-[#0C4B05] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                                ]"
                            >
                                {{ scheduleTitle }}
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'panelists'"
                                :class="[
                                    'shrink-0 whitespace-nowrap rounded-t-xl px-3 py-2.5 text-xs font-semibold transition sm:px-5 sm:py-3 sm:text-sm',
                                    activeTab === 'panelists' ? 'bg-[#0C4B05] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                                ]"
                            >
                                Assign Panelists
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'semester'"
                                :class="[
                                    'shrink-0 whitespace-nowrap rounded-t-xl px-3 py-2.5 text-xs font-semibold transition sm:px-5 sm:py-3 sm:text-sm',
                                    activeTab === 'semester' ? 'bg-[#0C4B05] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                                ]"
                            >
                                Semester
                            </button>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="p-3 sm:p-6">
                        <!-- ── Schedule Tab ── -->
                        <section v-if="activeTab === 'schedule'" class="rounded-2xl border border-gray-200 bg-gray-50 p-4 sm:p-5">
                            <!-- Reschedule Request Info -->
                            <div v-if="isRescheduleRequested" class="mb-4 rounded-2xl border border-gray-200 bg-white p-4 sm:mb-5 sm:p-5">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-gray-500">Requested Date</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">
                                            {{ requestedDate ? formatDate(requestedDate) : 'No requested date' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-gray-500">Requested Time</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">
                                            {{ requestedTime ? requestedTime : 'No requested time' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Read-only Schedule -->
                            <div v-if="hasSchedule && !canEditSchedule" class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-gray-500">Defense Date</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">
                                            {{ formatDate(project.schedule?.defense_date) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-gray-500">Defense Time</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">
                                            {{ project.schedule?.defense_time || 'No time provided' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-gray-500">Venue</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">
                                            {{ project.schedule?.venue || 'No venue provided' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Schedule Form -->
                            <div v-else>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <!-- Date -->
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-gray-700">Defense Date</label>
                                        <Popover v-slot="{ close }">
                                            <PopoverTrigger as-child>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    :class="
                                                        cn(
                                                            'h-[46px] w-full justify-start rounded-xl border-gray-300 bg-white px-4 text-left text-sm font-normal',
                                                            !defenseDate && 'text-muted-foreground',
                                                        )
                                                    "
                                                >
                                                    <CalendarIcon class="mr-2 h-4 w-4 shrink-0" />
                                                    <span class="truncate">
                                                        {{ defenseDate ? df.format(defenseDate.toDate(getLocalTimeZone())) : 'Pick a date' }}
                                                    </span>
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-auto p-0" align="start">
                                                <Calendar
                                                    v-model="defenseDate"
                                                    :default-placeholder="defaultPlaceholder"
                                                    :min-value="defaultPlaceholder"
                                                    layout="month-and-year"
                                                    initial-focus
                                                    @update:model-value="close"
                                                />
                                            </PopoverContent>
                                        </Popover>
                                    </div>

                                    <!-- Time -->
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-gray-700">Defense Time</label>
                                        <select
                                            v-model="defenseTime"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-[#0C4B05] focus:outline-none focus:ring-4 focus:ring-[#0C4B05]/10"
                                        >
                                            <option disabled value="">Select time slot</option>
                                            <option v-for="slot in timeSlots" :key="slot" :value="slot">
                                                {{ slot }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Venue -->
                                    <div class="sm:col-span-2 lg:col-span-1">
                                        <label class="mb-2 block text-sm font-semibold text-gray-700">Venue</label>
                                        <input
                                            v-model="venue"
                                            type="text"
                                            placeholder="Enter venue"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-[#0C4B05] focus:outline-none focus:ring-4 focus:ring-[#0C4B05]/10"
                                        />
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end sm:mt-5">
                                    <button
                                        type="button"
                                        @click="submitSchedule"
                                        :disabled="scheduleProcessing"
                                        class="w-full rounded-xl bg-[#0C4B05] px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-60 sm:w-auto"
                                    >
                                        {{ scheduleProcessing ? 'Saving...' : isRescheduleRequested ? 'Update Schedule' : 'Save Schedule' }}
                                    </button>
                                </div>
                            </div>
                        </section>

                        <!-- ── Panelists Tab ── -->
                        <section v-else-if="activeTab === 'panelists'" class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-2">
                            <!-- Assign Panel -->
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 sm:p-5">
                                <div class="mb-4">
                                    <h2 class="text-lg font-bold text-[#0C4B05]">Assign Panelists</h2>
                                    <p class="mt-1 text-sm text-gray-500">Select faculty members and assign them to the panel.</p>
                                </div>

                                <div v-if="!hasReachedMaximum" class="space-y-4">
                                    <!-- Department Filter -->
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-gray-700">Filter by Department</label>
                                        <select
                                            v-model="selectedDepartmentId"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-700 shadow-sm focus:border-[#0C4B05] focus:outline-none focus:ring-4 focus:ring-[#0C4B05]/10"
                                        >
                                            <option value="">All Departments</option>
                                            <option v-for="department in departments" :key="department.id" :value="department.id">
                                                {{ department.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Faculty Dropdown -->
                                    <div class="relative">
                                        <label class="mb-2 block text-sm font-semibold text-gray-700">Select Faculty</label>

                                        <button
                                            type="button"
                                            @click="showDropdown = !showDropdown"
                                            class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-3 text-left text-sm text-gray-700 shadow-sm transition hover:border-gray-400 focus:outline-none focus:ring-4 focus:ring-[#0C4B05]/10"
                                        >
                                            <span v-if="selectedPanelistData" class="min-w-0 flex-1 truncate pr-3">
                                                <span class="font-semibold text-gray-800">
                                                    {{ selectedPanelistData.first_name }} {{ selectedPanelistData.last_name }}
                                                </span>
                                                <span class="ml-1 hidden text-gray-500 sm:inline">— {{ selectedPanelistData.email }}</span>
                                            </span>
                                            <span v-else class="text-gray-400">Choose a faculty member</span>

                                            <svg
                                                class="h-4 w-4 shrink-0 text-gray-500"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div
                                            v-if="showDropdown"
                                            class="absolute z-10 mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg sm:max-h-72"
                                        >
                                            <div
                                                v-for="faculty in availableFaculties"
                                                :key="faculty.id"
                                                @click="selectFaculty(faculty)"
                                                class="cursor-pointer border-b border-gray-100 px-4 py-3 transition last:border-b-0 hover:bg-[#0C4B05]/5"
                                            >
                                                <p class="text-sm font-semibold text-gray-800">{{ faculty.first_name }} {{ faculty.last_name }}</p>
                                                <p class="truncate text-xs text-gray-500">{{ faculty.email }}</p>
                                                <p
                                                    v-if="faculty.department?.name"
                                                    class="mt-2 inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-[11px] text-gray-600"
                                                >
                                                    {{ faculty.department.name }}
                                                </p>
                                            </div>

                                            <div v-if="!availableFaculties.length" class="px-4 py-6 text-center text-sm text-gray-400">
                                                No available faculty found.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Error Message -->
                                    <div
                                        v-if="errorMessage"
                                        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600"
                                    >
                                        {{ errorMessage }}
                                    </div>

                                    <button
                                        type="button"
                                        @click="assignPanelist"
                                        :disabled="!selectedPanelist || panelistProcessing || hasReachedMaximum"
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-[#FFCD00] px-6 py-3 text-sm font-bold text-[#0C4B05] shadow-sm transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                       Add Panelist
                                    </button>
                                </div>

                                <div v-else class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm font-medium text-amber-700">
                                    Maximum of 3 panelists reached.
                                </div>
                            </div>

                            <!-- Current Panelists -->
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 sm:p-5">
                                <div class="mb-4">
                                    <h3 class="text-sm font-semibold text-gray-800">Current Panelists</h3>
                                    <p class="mt-1 text-xs text-gray-500">Assigned faculty members for this project.</p>
                                </div>

                                <div v-if="localPanelists.length" class="space-y-3">
                                    <div
                                        v-for="p in localPanelists"
                                        :key="p.id"
                                        class="rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-gray-800">{{ p.first_name }} {{ p.last_name }}</p>
                                                <p class="mt-1 text-xs text-gray-500">Panel Member</p>
                                                <p
                                                    v-if="p.department?.name"
                                                    class="mt-2 inline-flex rounded-full bg-[#0C4B05]/10 px-2.5 py-1 text-[11px] font-medium text-[#0C4B05]"
                                                >
                                                    {{ p.department.name }}
                                                </p>
                                            </div>
                                            <div class="shrink-0 rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-semibold text-green-700">
                                                Assigned
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-8 text-center text-sm text-gray-400"
                                >
                                    No panelists assigned yet.
                                </div>
                            </div>
                        </section>

                        <!-- ── Semester Tab ── -->
                        <section v-else class="rounded-2xl border border-gray-200 bg-gray-50 p-4 sm:p-5">
                            <div class="mb-4 sm:mb-5">
                                <h2 class="text-lg font-bold text-[#0C4B05]">Semester</h2>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 md:grid-cols-3">
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5">
                                    <p class="text-xs font-semibold uppercase text-gray-500">Current Semester</p>
                                    <p class="mt-2 text-base font-bold text-gray-900 sm:text-lg">{{ project.semester }}</p>
                                </div>

                                <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5">
                                    <p class="text-xs font-semibold uppercase text-gray-500">Project Status</p>
                                    <p class="mt-2 text-base font-bold text-gray-900 sm:text-lg">{{ project.status }}</p>
                                </div>

                                <div
                                    :class="[
                                        'rounded-2xl border bg-white p-4 sm:col-span-2 sm:p-5 md:col-span-1',
                                        isScheduleConfirmed ? 'border-green-200 bg-green-50' : 'border-gray-200',
                                    ]"
                                >
                                    <p :class="['text-xs font-semibold uppercase', isScheduleConfirmed ? 'text-green-700' : 'text-gray-500']">
                                        Schedule Status
                                    </p>
                                    <p :class="['mt-2 text-base font-bold sm:text-lg', isScheduleConfirmed ? 'text-green-800' : 'text-gray-900']">
                                        {{ scheduleStatusLabel }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="isFirstSemester" class="mt-5 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:mt-6 sm:p-5">
                                <p class="text-sm font-bold text-[#0C4B05]">First Semester Project</p>

                                <p class="mt-1 text-sm text-gray-600">
                                    Click the button below only if this project passed the 1st Semester. This will move the project to 2nd Semester
                                    and reset the schedule.
                                </p>

                                <div class="mt-4 sm:mt-5">
                                    <button
                                        type="button"
                                        @click="markFirstSemesterPassed"
                                        :disabled="semesterProcessing"
                                        class="w-full rounded-xl bg-[#0C4B05] px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                                    >
                                        {{ semesterProcessing ? 'Updating...' : 'Mark as Passed 1st Semester' }}
                                    </button>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </main>
        </SidebarInset>
    </SidebarProvider>
</template>
