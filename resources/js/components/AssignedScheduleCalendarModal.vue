<script setup lang="ts">

import { useDisplayFormatters } from '@/composables/useDisplayFormatter'
import type { User } from '@/types/user'
import { computed, watch } from 'vue'

type ProjectSchedule = {
    id: number
    defense_date: string
    defense_time: string
    venue: string | null
    status: string
}

type AssignedProject = {
    id: number
    title: string
    project_type?: string | null
    department?: string | null
    role: string
    adviser?: User | null
    panelists: User[]
    schedule?: ProjectSchedule | null
}

type ConfirmedScheduleItem = {
    id: number
    project_id: number
    title: string
    role: string
    defense_date: string
    defense_time: string
    venue: string | null
    status: string
    adviser?: User | null
    panelists: User[]
}

type CalendarProject = {
    id: number
    title: string
    adviser?: User | null
    panelists: User[]
    schedule: {
        defense_date: string
        defense_time: string
        venue: string | null
        status: string
    }
}

const props = defineProps<{
    show: boolean
    selectedProject: AssignedProject | null
    selectedDate: string | null
    confirmedSchedules: ConfirmedScheduleItem[]
    calendarProjects: CalendarProject[]
}>()

const emit = defineEmits<{
    (e: 'close'): void
    (e: 'dateClick', value: string): void
}>()

const { fullName, fullNames, formatDate, formatDefenseTime, mysqlTimeToSlot } = useDisplayFormatters()

watch(
    () => props.show,
    (isOpen) => {
        document.body.style.overflow = isOpen ? 'hidden' : ''
    },
    { immediate: true },
)

const schedulesForSelectedDate = computed(() => {
    if (!props.selectedDate) return []

    return props.confirmedSchedules.filter(
        (item) => item.defense_date === props.selectedDate,
    )
})

const relatedConflicts = computed(() => {
    if (!props.selectedProject?.schedule) return []

    const project = props.selectedProject
    const date = project.schedule.defense_date
    const time = mysqlTimeToSlot(project.schedule.defense_time)

    if (!date || !time) return []

    const selectedPanelistIds = (project.panelists || []).map((p) => p.id)
    const selectedAdviserId = project.adviser?.id || null
    const selectedVenue = (project.schedule.venue || '').trim().toLowerCase()

    return props.confirmedSchedules.filter((item) => {
        if (item.project_id === project.id) return false
        if (item.defense_date !== date) return false
        if (mysqlTimeToSlot(item.defense_time) !== time) return false

        const sameVenue =
            selectedVenue !== '' &&
            (item.venue || '').trim().toLowerCase() === selectedVenue

        const sameAdviser =
            !!selectedAdviserId && item.adviser?.id === selectedAdviserId

        const otherPanelistIds = (item.panelists || []).map((p) => p.id)
        const samePanelist = otherPanelistIds.some((id) => selectedPanelistIds.includes(id))

        return sameVenue || sameAdviser || samePanelist
    })
})

const handleClose = () => {
    emit('close')
}

const handleDateClick = (date: string) => {
    emit('dateClick', date)
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-4"
        @click="handleClose"
    >
        <div
            class="relative grid max-h-[90vh] w-full max-w-5xl grid-cols-1 overflow-hidden rounded-[28px] bg-white shadow-xl xl:grid-cols-[1.35fr_0.9fr]"
            @click.stop
        >
            <div class="flex min-h-0 flex-col p-6 lg:p-10">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-[#0C4B05]">
                        Confirmed Defense Calendar
                    </h2>
                    <p class="mt-2 text-sm text-gray-500">
                        These are your confirmed schedules as adviser or panelist.
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <ScheduleCalendarPanel
                        :projects="calendarProjects"
                        @dateClick="handleDateClick"
                    />
                </div>
            </div>

            <div class="flex max-h-[90vh] flex-col border-t bg-gray-50 xl:border-l xl:border-t-0">
                <div class="flex items-start justify-between gap-3 p-6 lg:p-8">
                    <h3 class="text-base font-semibold text-gray-800">
                        {{
                            selectedDate
                                ? `Confirmed schedules on ${selectedDate}`
                                : 'Confirmed schedules'
                        }}
                    </h3>

                    <button
                        type="button"
                        @click="handleClose"
                        class="shrink-0 rounded-lg p-1 text-3xl leading-none text-gray-500 hover:bg-gray-200 hover:text-black"
                    >
                        ✕
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 pb-6 lg:px-8 lg:pb-8">
                    <div
                        v-if="selectedDate && schedulesForSelectedDate.length"
                        class="space-y-4"
                    >
                        <div
                            v-for="item in schedulesForSelectedDate"
                            :key="item.id"
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                        >
                            <p class="break-words text-lg font-semibold text-gray-900">
                                {{ item.title }}
                            </p>
                            <p class="mt-2 text-sm text-gray-600">Role: {{ item.role }}</p>
                            <p class="text-sm text-gray-600">
                                Time: {{ formatDefenseTime(item.defense_time) }}
                            </p>
                            <p class="text-sm text-gray-600">Venue: {{ item.venue || 'TBA' }}</p>
                            <p class="text-sm text-gray-600">Adviser: {{ fullName(item.adviser) }}</p>
                            <p class="break-words text-sm text-gray-600">
                                Panelists: {{ fullNames(item.panelists) }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-else-if="selectedDate"
                        class="rounded-2xl border border-dashed bg-white p-5 text-sm text-gray-500"
                    >
                        No confirmed schedules found on this date.
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-dashed bg-white p-5 text-sm text-gray-500"
                    >
                        Select a date from the calendar to view confirmed schedules.
                    </div>

                    <div v-if="selectedProject?.schedule" class="mt-6 border-t pt-6">
                        <h3 class="text-base font-semibold text-gray-800">
                            Current selected project schedule
                        </h3>

                        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="break-words text-lg font-semibold text-gray-900">
                                {{ selectedProject.title }}
                            </p>
                            <p class="mt-2 text-sm text-gray-600">
                                Date: {{ formatDate(selectedProject.schedule.defense_date) }}
                            </p>
                            <p class="text-sm text-gray-600">
                                Time: {{ formatDefenseTime(selectedProject.schedule.defense_time) }}
                            </p>
                            <p class="text-sm text-gray-600">
                                Venue: {{ selectedProject.schedule.venue || 'TBA' }}
                            </p>
                        </div>

                        <div v-if="relatedConflicts.length" class="mt-4">
                            <h4 class="mb-2 text-sm font-semibold text-red-700">
                                Possible conflicts
                            </h4>

                            <div class="space-y-3">
                                <div
                                    v-for="conflict in relatedConflicts"
                                    :key="conflict.id"
                                    class="rounded-2xl border border-red-200 bg-red-50 p-4"
                                >
                                    <p class="break-words font-semibold text-red-800">
                                        {{ conflict.title }}
                                    </p>
                                    <p class="mt-1 text-sm text-red-700">Role: {{ conflict.role }}</p>
                                    <p class="text-sm text-red-700">
                                        Time: {{ formatDefenseTime(conflict.defense_time) }}
                                    </p>
                                    <p class="text-sm text-red-700">
                                        Venue: {{ conflict.venue || 'TBA' }}
                                    </p>
                                    <p class="text-sm text-red-700">
                                        Adviser: {{ fullName(conflict.adviser) }}
                                    </p>
                                    <p class="break-words text-sm text-red-700">
                                        Panelists: {{ fullNames(conflict.panelists) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-700"
                        >
                            No related conflicts found for this project schedule.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>