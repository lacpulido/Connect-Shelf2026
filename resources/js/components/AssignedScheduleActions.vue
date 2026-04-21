<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

type ProjectSchedule = {
    id: number;
    defense_date: string;
    defense_time: string;
    venue: string | null;
    status: string | null;
};

type AssignedProject = {
    id: number;
    title: string;
    role: string;
    schedule?: ProjectSchedule | null;
};

const props = defineProps<{
    project: AssignedProject;
}>();

const showRequestModal = ref(false);
const submitting = ref(false);

const initialForm = () => ({
    preferred_date: '',
    preferred_time: '',
    preferred_venue: '',
    reason: '',
});

const form = ref(initialForm());

const normalizedStatus = computed(() =>
    (props.project.schedule?.status ?? '').toString().trim().toLowerCase()
);

const requestStatuses = [
    'reschedule_requested',
    'for_reschedule',
    'requested_reschedule',
    'reschedule request',
    'reschedule_requested_by_student',
    'reschedule_requested_by_adviser',
    'reschedule_requested_by_panelist',
    'reschedule_requested_by_faculty',
];

const isConfirmed = computed(() => normalizedStatus.value === 'confirmed');
const hasRequestAlready = computed(() => requestStatuses.includes(normalizedStatus.value));

const canConfirm = computed(() => !!props.project.schedule?.id && !isConfirmed.value);
const canRequest = computed(() => !!props.project.schedule?.id && !isConfirmed.value && !hasRequestAlready.value);
const canReschedule = computed(() => !!props.project.schedule?.id && !isConfirmed.value && hasRequestAlready.value);

const resetForm = () => {
    form.value = initialForm();
};

const closeRequestModal = () => {
    if (submitting.value) return;

    showRequestModal.value = false;
    resetForm();
};

const openRequestModal = () => {
    if (!canRequest.value || submitting.value) return;

    resetForm();
    showRequestModal.value = true;
};

const openRescheduleModal = () => {
    if (!canReschedule.value || submitting.value) return;

    resetForm();
    form.value.preferred_date = props.project.schedule?.defense_date ?? '';
    form.value.preferred_time = props.project.schedule?.defense_time ?? '';
    form.value.preferred_venue = props.project.schedule?.venue ?? '';
    showRequestModal.value = true;
};

const submitConfirm = () => {
    if (!props.project.schedule?.id || submitting.value || isConfirmed.value) return;

    Swal.fire({
        title: 'Confirm schedule?',
        text: 'This will mark the schedule as confirmed.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, confirm',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0C4B05',
    }).then((result) => {
        if (!result.isConfirmed) return;

        submitting.value = true;

       router.post(
    route('faculty.defense-schedules.respond', {
        schedule: props.project.schedule!.id,
    }),
    {
        action: 'confirm',
    },
    {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Confirmed',
                text: 'Schedule confirmed successfully.',
                confirmButtonColor: '#0C4B05',
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Unable to confirm',
                text: 'Please try again.',
                confirmButtonColor: '#0C4B05',
            });
        },
        onFinish: () => {
            submitting.value = false;
        },
    }
);
    });
};

const submitRequest = () => {
    if (!props.project.schedule?.id || submitting.value) return;

    if (
        !form.value.preferred_date &&
        !form.value.preferred_time &&
        !form.value.preferred_venue &&
        !form.value.reason
    ) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing details',
            text: 'Please provide at least one preferred schedule detail or a reason.',
            confirmButtonColor: '#0C4B05',
        });
        return;
    }

    submitting.value = true;

    router.post(
    route('faculty.defense-schedules.respond', {
        schedule: props.project.schedule!.id,
    }),
    {
        action: 'confirm',
    },
    {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Confirmed',
                text: 'Schedule confirmed successfully.',
                confirmButtonColor: '#0C4B05',
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Unable to confirm',
                text: 'Please try again.',
                confirmButtonColor: '#0C4B05',
            });
        },
        onFinish: () => {
            submitting.value = false;
        },
    }
);
};
</script>

<template>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <button
            v-if="canConfirm"
            type="button"
            @click="submitConfirm"
            :disabled="submitting"
            class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0C4B05] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#083603] disabled:cursor-not-allowed disabled:opacity-60"
        >
            Confirm
        </button>

        <button
            v-if="canRequest"
            type="button"
            @click="openRequestModal"
            :disabled="submitting"
            class="inline-flex h-10 items-center justify-center rounded-lg border border-[#0C4B05] bg-white px-4 text-sm font-semibold text-[#0C4B05] shadow-sm transition hover:bg-green-50 disabled:cursor-not-allowed disabled:opacity-60"
        >
            Request Reschedule
        </button>

        <button
            v-if="canReschedule"
            type="button"
            @click="openRescheduleModal"
            :disabled="submitting"
            class="inline-flex h-10 items-center justify-center rounded-lg bg-amber-500 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60"
        >
            Reschedule
        </button>
    </div>

    <teleport to="body">
        <div
            v-if="showRequestModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                <div class="border-b px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{ canReschedule ? 'Reschedule Defense' : 'Request Reschedule' }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Provide at least one preferred detail or a reason.
                    </p>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Preferred Date
                        </label>
                        <input
                            v-model="form.preferred_date"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#0C4B05] focus:outline-none focus:ring-0"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Preferred Time
                        </label>
                        <input
                            v-model="form.preferred_time"
                            type="time"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#0C4B05] focus:outline-none focus:ring-0"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Preferred Venue
                        </label>
                        <input
                            v-model="form.preferred_venue"
                            type="text"
                            placeholder="Enter preferred venue"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#0C4B05] focus:outline-none focus:ring-0"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Reason
                        </label>
                        <textarea
                            v-model="form.reason"
                            rows="4"
                            placeholder="State the reason for requesting reschedule"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#0C4B05] focus:outline-none focus:ring-0"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t px-6 py-4">
                    <button
                        type="button"
                        @click="closeRequestModal"
                        :disabled="submitting"
                        class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-60"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="submitRequest"
                        :disabled="submitting"
                        class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0C4B05] px-4 text-sm font-semibold text-white transition hover:bg-[#083603] disabled:opacity-60"
                    >
                        {{ canReschedule ? 'Save Reschedule' : 'Submit Request' }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>