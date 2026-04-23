<script setup lang="ts">
import axios from 'axios'
import { ref } from 'vue'
import { useAlerts } from '@/composables/useAlerts'

interface Schedule {
    id: number
    project_title: string
    defense_date: string | null
    defense_time: string | null
    venue: string | null
}

const props = defineProps<{
    show: boolean
    schedule: Schedule | null
}>()

const emit = defineEmits<{
    (e: 'close'): void
    (e: 'confirmed'): void
}>()

const submitting = ref(false)

const { showSuccessAlert, showErrorAlert } = useAlerts()

const closeModal = () => {
    if (!submitting.value) emit('close')
}

const confirmSchedule = async () => {
    if (!props.schedule || submitting.value) return

    submitting.value = true

    try {
        await axios.post(`/faculty/schedules/${props.schedule.id}/confirm`)

        emit('confirmed')
        emit('close')

        await showSuccessAlert(
            'Confirmed!',
            'Schedule confirmed successfully.'
        )
    } catch (error: any) {
        const message =
            error?.response?.data?.message ||
            'Failed to confirm the schedule.'

        await showErrorAlert('Error', message)
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 px-4 pt-14 pb-6 backdrop-blur-sm"
            @click.self="closeModal"
        >
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div>
                        <h2 class="text-xl font-bold text-[#0C4B05]">
                            Confirm Schedule
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Review the defense schedule details before confirming.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="closeModal"
                        :disabled="submitting"
                        class="rounded-md px-3 py-1 text-sm text-gray-600 hover:opacity-90 disabled:opacity-50"
                    >
                        ✕
                    </button>
                </div>

                <div class="px-6 py-5">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-800">
                                Confirmation Details
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Are you sure you want to confirm this defense schedule?
                            </p>
                        </div>

                        <div class="space-y-3 text-sm text-gray-700">
                            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                <span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Date
                                </span>
                                <p class="mt-1 font-medium text-gray-800">
                                    {{ schedule?.defense_date || 'N/A' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                <span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Time
                                </span>
                                <p class="mt-1 font-medium text-gray-800">
                                    {{ schedule?.defense_time || 'N/A' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                <span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Venue
                                </span>
                                <p class="mt-1 font-medium text-gray-800">
                                    {{ schedule?.venue || 'TBA' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-3">
                        <button
                            type="button"
                            @click="confirmSchedule"
                            :disabled="submitting"
                            class="rounded-md bg-[#0C4B05] px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ submitting ? 'Confirming...' : 'Confirm Schedule' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>