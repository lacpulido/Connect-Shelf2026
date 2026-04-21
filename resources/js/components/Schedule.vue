<script setup lang="ts">
import { computed } from 'vue'

interface RawPanelist {
    first_name?: string
    last_name?: string
    name?: string
    department?: {
        name?: string
    } | string
}

interface ScheduleData {
    id?: number
    defense_date: string
    defense_time: string
    venue: string | null
    status?: string
    panelists?: RawPanelist[]
    project?: {
        title?: string
    }
}

const props = defineProps<{
    schedule?: ScheduleData | null
}>()

const finalSchedule = computed(() => props.schedule ?? null)

const panelists = computed(() => {
    if (!finalSchedule.value?.panelists) return []

    return finalSchedule.value.panelists.map((p) => ({
        name:
            p.name ||
            `${p.first_name ?? ''} ${p.last_name ?? ''}`.trim() ||
            'N/A',
        department:
            typeof p.department === 'string'
                ? p.department
                : p.department?.name || 'N/A',
    }))
})

const statusLabel = computed(() => {
    return finalSchedule.value?.status === 'confirmed' ? 'Confirmed' : 'Pending'
})

const statusClasses = 'border-gray-300 text-gray-700'

function formatDate(date: string) {
    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}
</script>

<template>
    <div class="rounded-2xl border bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[#0C4B05]">
                Defense Details
            </h2>

            <span
                v-if="finalSchedule"
                :class="[
                    'inline-flex items-center rounded-full border px-4 py-1.5 text-xs font-semibold',
                    statusClasses,
                ]"
            >
                {{ statusLabel }}
            </span>
        </div>

        <div v-if="!finalSchedule" class="text-sm text-gray-500">
            No defense schedule yet
        </div>

        <div v-else class="space-y-4 text-sm">
            <div class="flex items-center justify-between gap-4">
                <span class="text-gray-600">Date</span>
                <span class="text-right font-medium text-gray-900">
                    {{ finalSchedule.defense_date ? formatDate(finalSchedule.defense_date) : 'TBA' }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-4">
                <span class="text-gray-600">Time</span>
                <span class="text-right font-medium text-gray-900">
                    {{ finalSchedule.defense_time || 'TBA' }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-4">
                <span class="text-gray-600">Venue</span>
                <span class="text-right font-medium text-gray-900">
                    {{ finalSchedule.venue || 'TBA' }}
                </span>
            </div>

            <div>
                <span class="mb-2 block font-medium text-gray-700">
                    Panelists
                </span>

                <ul v-if="panelists.length" class="list-disc space-y-1 pl-5 text-gray-800">
                    <li v-for="(p, i) in panelists" :key="i">
                        {{ p.name }} — <span class="text-gray-500">{{ p.department }}</span>
                    </li>
                </ul>

                <div v-else class="text-sm text-gray-400">
                    No panelists assigned
                </div>
            </div>
        </div>
    </div>
</template>