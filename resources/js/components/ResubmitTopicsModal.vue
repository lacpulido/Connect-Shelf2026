<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileText, Loader2, X } from 'lucide-vue-next';

const props = defineProps<{
    show: boolean;
    projectSlug: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

const form = useForm({
    proposal_titles: ['', '', ''],
    proposal_files: [null, null, null] as (File | null)[],
});

const closeModal = () => {
    if (form.processing) return;

    form.reset();
    form.clearErrors();
    emit('close');
};

const handleFileChange = (event: Event, index: number) => {
    const input = event.target as HTMLInputElement;
    form.proposal_files[index] = input.files?.[0] ?? null;
};

const submit = () => {
    form.post(route('student.projects.resubmit-topics', props.projectSlug), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="closeModal"
        >
            <div class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Resubmit Topic Proposals
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Submit another set of 3 topics with one file per topic.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-full p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
                        @click="closeModal"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submit">
                    <div class="max-h-[70vh] space-y-5 overflow-y-auto px-5 py-5">
                        <div
                            v-for="(_, index) in form.proposal_titles"
                            :key="index"
                            class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-gray-700">
                                    Topic Proposal {{ index + 1 }}
                                </label>

                                <input
                                    v-model="form.proposal_titles[index]"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-[#0C4B05] focus:ring-2 focus:ring-[#0C4B05]/20"
                                    :placeholder="`Enter topic proposal ${index + 1}`"
                                />

                                <p
                                    v-if="form.errors[`proposal_titles.${index}`]"
                                    class="text-xs text-red-600"
                                >
                                    {{ form.errors[`proposal_titles.${index}`] }}
                                </p>
                            </div>

                            <div class="mt-4 space-y-2">
                                <label class="text-sm font-semibold text-gray-700">
                                    Submission File for Topic {{ index + 1 }}
                                </label>

                                <label
                                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-center transition hover:bg-gray-100"
                                >
                                    <FileText class="h-8 w-8 text-gray-500" />

                                    <span class="mt-2 text-sm font-medium text-gray-700">
                                        {{ form.proposal_files[index]?.name ?? `Choose file for topic ${index + 1}` }}
                                    </span>

                                    <span class="mt-1 text-xs text-gray-500">
                                        PDF, DOC, or DOCX only
                                    </span>

                                    <input
                                        type="file"
                                        class="hidden"
                                        accept=".pdf,.doc,.docx"
                                        @change="handleFileChange($event, index)"
                                    />
                                </label>

                                <p
                                    v-if="form.errors[`proposal_files.${index}`]"
                                    class="text-xs text-red-600"
                                >
                                    {{ form.errors[`proposal_files.${index}`] }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
                            @click="closeModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0C4B05] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a3d04] disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin"
                            />

                            Submit New Topics
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>