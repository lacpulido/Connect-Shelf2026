<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { ref } from 'vue';

const props = defineProps<{
    label?: string;
    submissionId?: number | null;
    isResubmit?: boolean;
    existingTitle?: string;
    sectionTitle?: string;
}>();

const showModal = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    title: '',
    document: null as File | null,
});

const openModal = () => {
    resetForm();
    showModal.value = true;

    if (props.isResubmit && props.existingTitle) {
        form.title = props.existingTitle;
    } else if (props.sectionTitle) {
        form.title = props.sectionTitle;
    }
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const resetForm = () => {
    form.reset();

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    if (!form.title) {
        form.title = props.sectionTitle ?? 'New Submission';
    }

    const routeName = props.isResubmit ? route('student.submissions.resubmit', props.submissionId) : route('student.submit-paper.store');

    form.post(routeName, {
        forceFormData: true,
        onSuccess: () => {
            closeModal();

            Swal.fire({
                icon: 'success',
                title: props.isResubmit ? 'Resubmitted' : 'Submitted',
                text: props.isResubmit ? 'Paper resubmitted successfully.' : 'Paper submitted successfully.',
                confirmButtonColor: '#0C4B05',
                confirmButtonText: 'OK',
                width: 320,
                padding: '1rem',
                iconColor: '#16a34a',
                customClass: {
                    popup: 'rounded-xl',
                    title: 'text-lg font-semibold',
                    htmlContainer: 'text-sm',
                    confirmButton: 'px-4 py-1.5 text-sm rounded-md',
                },
            });
        },
        onError: (errors) => {
            console.error('Submission Errors:', errors);

            Swal.fire({
                icon: 'error',
                title: 'Submission Failed',
                text: Object.values(errors)[0] || 'Please check your inputs and try again.',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'OK',
                width: 320,
                padding: '1rem',
                customClass: {
                    popup: 'rounded-xl',
                    title: 'text-lg font-semibold',
                    htmlContainer: 'text-sm',
                    confirmButton: 'px-4 py-1.5 text-sm rounded-md',
                },
            });
        },
    });
};
</script>

<template>
    <!-- Trigger Button -->
    <button
        @click="openModal"
        class="inline-flex items-center gap-2 rounded-xl bg-[#0C4B05] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#083803]"
    >
        {{ label ?? (isResubmit ? 'Resubmit Paper' : 'Upload File') }}
    </button>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" @click.self="closeModal">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b px-5 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ isResubmit ? 'Resubmit Paper' : 'Upload File' }}
                </h2>

                <button type="button" @click="closeModal" class="rounded-md p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                    ✕
                </button>
            </div>

            <form @submit.prevent="submit">
                <!-- Body -->
                <div class="space-y-4 px-5 py-5">
                    <input v-model="form.title" type="hidden" />

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700"> Upload File </label>

                        <input
                            ref="fileInput"
                            type="file"
                            accept="application/pdf"
                            required
                            @change="(e) => (form.document = (e.target as HTMLInputElement).files?.[0] ?? null)"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-700 outline-none transition file:mr-3 file:rounded-md file:border-0 file:bg-[#0C4B05] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#083803] focus:border-[#0C4B05] focus:ring-2 focus:ring-[#0C4B05]/20"
                        />

                        <p class="mt-2 text-sm text-gray-500">Only PDF files are allowed.</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 border-t px-5 py-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-[#0C4B05] px-4 py-2 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ form.processing ? 'Uploading...' : 'Upload' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
