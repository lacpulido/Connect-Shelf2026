<script setup lang="ts">
import { useDateFormatter } from '@/composables/useDateFormatter'

interface Schedule {
    id: number
    title: string
    date: string
}

const props = defineProps<{
    schedules: Schedule[]
}>()

const { formatDateTime } = useDateFormatter()
</script>

<template>
    <div class="rounded-2xl border bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold">Upcoming Events</h2>

        <div v-if="props.schedules.length" class="space-y-4">
            <div
                v-for="event in props.schedules"
                :key="event.id"
                class="flex items-center gap-4"
            >
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-700 font-bold text-white"
                >
                    {{ new Date(event.date).getDate() }}
                </div>

                <div>
                    <p class="font-medium">
                        {{ event.title }}
                    </p>

                    <p class="text-sm text-gray-500">
                        {{ formatDateTime(event.date) }}
                    </p>
                </div>
            </div>
        </div>

        <div v-else class="text-sm text-gray-500">
            No upcoming schedule.
        </div>
    </div>
</template>