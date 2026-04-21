<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { useAlerts } from '@/composables/useAlerts'

type Schedule = {
    defense_date?: string | null
    defense_time?: string | null
    venue?: string | null
}

type Project = {
    id: number
    schedule?: Schedule | null
}

const props = defineProps<{
    show: boolean
    project: Project | null
}>()

const emit = defineEmits<{
    (e: 'close'): void
    (e: 'saved', projectId: number): void
}>()

const { showSuccessAlert, showErrorAlert, showWarningAlert } = useAlerts()

const defenseDate = ref<string | null>(null)
const defenseTime = ref<string | null>(null)
const venue = ref<string>('')

const processing = ref(false)

const today = computed(() => {
    const d = new Date()
    return d.toISOString().split('T')[0]
})

const timeSlots = [
    '8:00 AM - 11:00 AM',
    '11:00 AM - 2:00 PM',
    '2:00 PM - 5:00 PM',
]

watch(
    () => props.project,
    (project) => {
        if (project?.schedule) {
            defenseDate.value = project.schedule.defense_date ?? null
            defenseTime.value = project.schedule.defense_time ?? null
            venue.value = project.schedule.venue ?? ''
        } else {
            defenseDate.value = null
            defenseTime.value = null
            venue.value = ''
        }
    },
    { immediate: true },
)

const closeModal = () => {
    emit('close')
}

const submitSchedule = () => {
    const projectId = props.project?.id

    if (!projectId) {
        showErrorAlert('No project selected', 'Please select a project first.')
        return
    }

    if (!defenseDate.value || !defenseTime.value || !venue.value.trim()) {
        showWarningAlert(
            'Missing fields',
            'Please fill in defense date, time, and venue.',
        )
        return
    }

    processing.value = true

    router.post(
        route('focalperson.schedule.store'),
        {
            project_id: projectId,
            defense_date: defenseDate.value,
            defense_time: defenseTime.value,
            venue: venue.value.trim(),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showSuccessAlert(
                    'Schedule Saved',
                    'The defense schedule has been set successfully.',
                )

                emit('saved', projectId)
                emit('close')
            },
            onError: () => {
                showErrorAlert(
                    'Failed',
                    'Unable to save the schedule.',
                )
            },
            onFinish: () => {
                processing.value = false
            },
        },
    )
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <!-- HEADER -->
            <div class="flex items-center justify-between border-b px-5 py-4">
                <h2 class="text-lg font-semibold">
                    {{
                        project?.schedule
                            ? 'Update Defense Schedule'
                            : 'Set Defense Schedule'
                    }}
                </h2>

                <button
                    @click="closeModal"
                    class="text-xl text-gray-500 transition hover:text-gray-700"
                >
                    ✕
                </button>
            </div>

            <!-- BODY -->
            <div class="space-y-4 px-5 py-5">
                <!-- DATE -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Defense Date
                    </label>
                    <input
                        v-model="defenseDate"
                        type="date"
                        :min="today"
                        class="w-full rounded-lg border px-4 py-2 focus:border-green-700 focus:outline-none"
                    />
                </div>

                <!-- TIME -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Defense Time
                    </label>
                    <select
                        v-model="defenseTime"
                        class="w-full rounded-lg border px-4 py-2 focus:border-green-700 focus:outline-none"
                    >
                        <option disabled value="">Select time slot</option>
                        <option
                            v-for="slot in timeSlots"
                            :key="slot"
                            :value="slot"
                        >
                            {{ slot }}
                        </option>
                    </select>
                </div>

                <!-- VENUE -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-red-600">
                        Venue (Required)
                    </label>
                    <input
                        v-model="venue"
                        type="text"
                        placeholder="Enter venue"
                        class="w-full rounded-lg border px-4 py-2 focus:border-green-700 focus:outline-none"
                    />
                </div>
            </div>

            <!-- FOOTER -->
            <div class="flex justify-end gap-3 border-t px-5 py-4">
                <button
                    @click="closeModal"
                    class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                >
                    Cancel
                </button>

                <button
                    @click="submitSchedule"
                    :disabled="processing"
                    class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-800 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ processing ? 'Saving...' : 'Save Schedule' }}
                </button>
            </div>
        </div>
    </div>
</template>