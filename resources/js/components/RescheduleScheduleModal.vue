<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'
import { useAlerts } from '@/composables/useAlerts'
import { cn } from '@/lib/utils'
import type { DateValue } from '@internationalized/date'
import {
    DateFormatter,
    getLocalTimeZone,
    parseDate,
    today,
} from '@internationalized/date'
import { router } from '@inertiajs/vue3'
import { CalendarIcon } from 'lucide-vue-next'
import { ref, watch } from 'vue'

interface Schedule {
    id: number
    project_title: string
    defense_date: string | null
    defense_time: string | null
    requested_defense_date?: string | null
    requested_defense_time?: string | null
}

const props = defineProps<{
    show: boolean
    schedule: Schedule | null
}>()

const emit = defineEmits<{
    (e: 'close'): void
    (e: 'submitted'): void
}>()

const { showSuccessAlert, showWarningAlert, showErrorAlert } = useAlerts()

const preferredDate = ref<DateValue>()
const preferredTime = ref('')
const submitting = ref(false)

const allowedTimeSlots = [
    '8:00 AM - 11:00 AM',
    '11:00 AM - 2:00 PM',
    '2:00 PM - 5:00 PM',
]

const defaultPlaceholder = today(getLocalTimeZone())

const df = new DateFormatter('en-US', {
    dateStyle: 'long',
})

const toDateValue = (date?: string | null) => {
    if (!date) return undefined

    try {
        return parseDate(date)
    } catch {
        return undefined
    }
}

const toBackendDate = (date?: DateValue) => {
    if (!date) return ''

    return date.toDate(getLocalTimeZone()).toISOString().split('T')[0]
}

watch(
    () => props.show,
    (visible) => {
        if (visible && props.schedule) {
            const dateToUse =
                props.schedule.requested_defense_date ??
                props.schedule.defense_date

            const timeToUse =
                props.schedule.requested_defense_time ??
                props.schedule.defense_time

            preferredDate.value = toDateValue(dateToUse)
            preferredTime.value = timeToUse ?? ''
        } else {
            preferredDate.value = undefined
            preferredTime.value = ''
            submitting.value = false
        }
    },
)

const closeModal = () => {
    if (!submitting.value) emit('close')
}

const submitReschedule = () => {
    if (!props.schedule || submitting.value) return

    const formattedPreferredDate = toBackendDate(preferredDate.value)

    if (!formattedPreferredDate || !preferredTime.value) {
        showWarningAlert('Missing Fields', 'Please fill in all fields.')
        return
    }

    submitting.value = true

    router.post(
        route('faculty.schedules.request-reschedule', {
            id: props.schedule.id,
        }),
        {
            preferred_date: formattedPreferredDate,
            preferred_time: preferredTime.value,
        },
        {
            preserveScroll: true,
            onSuccess: async () => {
                emit('submitted')
                emit('close')
                await showSuccessAlert('Success', 'Reschedule request submitted successfully.')
            },
            onError: async () => {
                await showErrorAlert('Error', 'Failed to submit reschedule request.')
            },
            onFinish: () => {
                submitting.value = false
            },
        },
    )
}
</script>

<template>
    <teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
            @click.self="closeModal"
        >
            <div class="mt-10 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div>
                        <h2 class="text-xl font-bold text-[#0C4B05]">
                            Reschedule
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Update your preferred defense schedule.
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
                        <div class="space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">
                                    Preferred Date
                                </label>

                                <Popover>
                                    <PopoverTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            :class="
                                                cn(
                                                    'h-[46px] w-full justify-start rounded-xl border-gray-300 bg-white px-4 text-left text-sm font-normal text-gray-700 shadow-none hover:bg-white',
                                                    !preferredDate && 'text-muted-foreground',
                                                )
                                            "
                                        >
                                            <CalendarIcon class="mr-2 h-4 w-4" />
                                            {{
                                                preferredDate
                                                    ? df.format(preferredDate.toDate(getLocalTimeZone()))
                                                    : 'Pick a date'
                                            }}
                                        </Button>
                                    </PopoverTrigger>

                                    <PopoverContent class="w-auto p-0" align="start">
                                        <Calendar
                                            v-model="preferredDate"
                                            :default-placeholder="defaultPlaceholder"
                                            :min-value="defaultPlaceholder"
                                            layout="month-and-year"
                                            initial-focus
                                        />
                                    </PopoverContent>
                                </Popover>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">
                                    Preferred Time
                                </label>

                                <select
                                    v-model="preferredTime"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-700 focus:border-[#0C4B05] focus:outline-none focus:ring-2 focus:ring-[#0C4B05]/20"
                                >
                                    <option value="">Select time</option>
                                    <option
                                        v-for="time in allowedTimeSlots"
                                        :key="time"
                                        :value="time"
                                    >
                                        {{ time }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button
                            type="button"
                            @click="submitReschedule"
                            :disabled="submitting"
                            class="rounded-md bg-[#0C4B05] px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ submitting ? 'Submitting...' : 'Submit Request' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>