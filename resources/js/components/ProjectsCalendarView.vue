<script setup lang="ts">
import { computed, ref, watch } from 'vue'

type ProjectSchedule = {
    id?: number
    status?: string | null
    is_confirmed?: boolean | number | null
    confirmation_status?: string | null
    defense_date?: string | null
    defense_time?: string | null
    venue?: string | null
} | null

type Project = {
    id: number
    title: string
    description?: string
    adviser?: any | null
    user?: any | null
    schedule?: ProjectSchedule
    status?: string
}

const props = defineProps<{
    show: boolean
    projects: Project[]
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const closeModal = () => emit('close')

const today = new Date()
const currentMonth = ref(today.getMonth())
const currentYear = ref(today.getFullYear())

watch(
    () => props.show,
    (value) => {
        if (value) {
            currentMonth.value = today.getMonth()
            currentYear.value = today.getFullYear()
        }
    },
    { immediate: true },
)

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const monthYearLabel = computed(() => {
    return new Date(currentYear.value, currentMonth.value).toLocaleDateString(
        undefined,
        { month: 'long', year: 'numeric' },
    )
})

const normalizedSchedules = computed(() => {
    return props.projects
        .filter((project) => !!project.schedule?.defense_date)
        .map((project) => {
            const dateObj = new Date(project.schedule!.defense_date as string)

            return {
                id: project.id,
                project_title: project.title,
                created_by: project.user
                    ? `${project.user.first_name ?? ''} ${project.user.last_name ?? ''}`.trim()
                    : 'N/A',
                defense_date: project.schedule?.defense_date || '',
                defense_time: project.schedule?.defense_time || '',
                venue: project.schedule?.venue || null,
                adviser: project.adviser
                    ? `${project.adviser.first_name ?? ''} ${project.adviser.last_name ?? ''}`.trim()
                    : 'Not Assigned',
                year: dateObj.getFullYear(),
                month: dateObj.getMonth(),
                day: dateObj.getDate(),
            }
        })
})

const calendarDays = computed(() => {
    const firstDay = new Date(currentYear.value, currentMonth.value, 1)
    const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0)

    const daysInMonth = lastDay.getDate()
    const startDayOfWeek = firstDay.getDay()

    const days: Array<{
        day: number | null
        schedules: any[]
        isToday: boolean
        isCurrentMonth: boolean
    }> = []

    for (let i = 0; i < startDayOfWeek; i++) {
        days.push({
            day: null,
            schedules: [],
            isToday: false,
            isCurrentMonth: false,
        })
    }

    const todayString = new Date().toDateString()

    for (let day = 1; day <= daysInMonth; day++) {
        const cellDate = new Date(currentYear.value, currentMonth.value, day)

        const daySchedules = normalizedSchedules.value.filter(
            (schedule) =>
                schedule.year === currentYear.value &&
                schedule.month === currentMonth.value &&
                schedule.day === day,
        )

        days.push({
            day,
            schedules: daySchedules,
            isToday: cellDate.toDateString() === todayString,
            isCurrentMonth: true,
        })
    }

    while (days.length % 7 !== 0) {
        days.push({
            day: null,
            schedules: [],
            isToday: false,
            isCurrentMonth: false,
        })
    }

    return days
})

const schedulesForCurrentMonth = computed(() => {
    return normalizedSchedules.value.filter(
        (schedule) =>
            schedule.year === currentYear.value &&
            schedule.month === currentMonth.value,
    )
})

const previousMonth = () => {
    if (currentMonth.value === 0) {
        currentMonth.value = 11
        currentYear.value--
    } else {
        currentMonth.value--
    }
}

const nextMonth = () => {
    if (currentMonth.value === 11) {
        currentMonth.value = 0
        currentYear.value++
    } else {
        currentMonth.value++
    }
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

const formatTime = (time?: string | null) => {
    if (!time) return 'TBA'

    const parts = time.split(':')
    if (parts.length < 2) return time

    const hour = Number(parts[0])
    const minute = parts[1]

    if (Number.isNaN(hour)) return time

    const suffix = hour >= 12 ? 'PM' : 'AM'
    const formattedHour = hour % 12 || 12

    return `${formattedHour}:${minute} ${suffix}`
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeModal"
    >
        <div class="h-[75vh] w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b px-5 py-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        Defense Schedule Calendar
                    </h2>
                    <p class="text-xs text-gray-500">
                        View all defense schedules
                    </p>
                </div>

                <button
                    @click="closeModal"
                    class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100"
                >
                    ✕
                </button>
            </div>

            <div class="grid h-[calc(75vh-70px)] grid-cols-1 md:grid-cols-3">
                <div class="overflow-y-auto border-r p-4 md:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <button
                            @click="previousMonth"
                            class="rounded-md border px-3 py-1 text-sm transition hover:bg-gray-100"
                        >
                            ←
                        </button>

                        <h3 class="text-md font-semibold">
                            {{ monthYearLabel }}
                        </h3>

                        <button
                            @click="nextMonth"
                            class="rounded-md border px-3 py-1 text-sm transition hover:bg-gray-100"
                        >
                            →
                        </button>
                    </div>

                    <div class="grid grid-cols-7 gap-1 text-xs font-semibold text-gray-600">
                        <div
                            v-for="day in weekDays"
                            :key="day"
                            class="text-center"
                        >
                            {{ day }}
                        </div>
                    </div>

                    <div class="mt-1 grid grid-cols-7 gap-1">
                        <div
                            v-for="(day, index) in calendarDays"
                            :key="index"
                            class="min-h-[80px] rounded-lg border p-1 text-xs"
                            :class="[
                                day.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
                                day.isToday ? 'ring-2 ring-green-700' : '',
                            ]"
                        >
                            <template v-if="day.day">
                                <div class="flex justify-between">
                                    <span
                                        class="font-bold"
                                        :class="day.isToday ? 'text-green-700' : ''"
                                    >
                                        {{ day.day }}
                                    </span>

                                    <span
                                        v-if="day.schedules.length"
                                        class="rounded-full bg-yellow-400 px-1 text-[10px] font-bold text-gray-900"
                                    >
                                        {{ day.schedules.length }}
                                    </span>
                                </div>

                                <div class="mt-1 space-y-1">
                                    <div
                                        v-for="schedule in day.schedules.slice(0, 2)"
                                        :key="schedule.id"
                                        class="truncate rounded bg-green-100 px-1 py-0.5 text-[10px] font-medium text-green-800"
                                        :title="schedule.project_title"
                                    >
                                        {{ schedule.project_title }}
                                    </div>

                                    <div
                                        v-if="day.schedules.length > 2"
                                        class="text-[10px] font-semibold text-gray-500"
                                    >
                                        +{{ day.schedules.length - 2 }} more
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="overflow-y-auto p-4">
                    <h3 class="text-sm font-bold">
                        {{ monthYearLabel }}
                    </h3>

                    <div
                        v-if="schedulesForCurrentMonth.length"
                        class="mt-3 space-y-2"
                    >
                        <div
                            v-for="schedule in schedulesForCurrentMonth"
                            :key="schedule.id"
                            class="rounded-lg border bg-gray-50 p-2 text-xs"
                        >
                            <p class="font-semibold text-gray-900">
                                {{ schedule.project_title }}
                            </p>
                            <p class="mt-1 text-gray-600">
                                {{ formatDate(schedule.defense_date) }}
                            </p>
                            <p class="text-gray-600">
                                {{ formatTime(schedule.defense_time) }}
                            </p>
                            <p class="text-gray-600">
                                {{ schedule.venue || 'TBA' }}
                            </p>
                            <p class="mt-1 text-gray-500">
                                Adviser: {{ schedule.adviser }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-else
                        class="mt-4 text-center text-xs text-gray-500"
                    >
                        No schedules this month.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>