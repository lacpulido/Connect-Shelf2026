<script setup lang="ts">
import { BellRing } from 'lucide-vue-next'

defineProps<{
    show: boolean
    title?: string
    message?: string
}>()

const emit = defineEmits<{
    (e: 'close'): void
    (e: 'go-to-projects'): void
}>()
</script>

<template>
    <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#edf5ec]">
                        <BellRing class="h-6 w-6 text-[#0C4B05]" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-[#0C4B05]">
                            {{ title || 'Focal Person Assigned' }}
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            {{ message || 'You have been assigned as the Focal Person of your department.' }}
                        </p>

                        <p class="mt-1 text-sm leading-6 text-gray-600">
                            You can now manage focal person tasks and view assigned projects.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                    >
                        Close
                    </button>

                    <button
                        type="button"
                        @click="emit('go-to-projects')"
                        class="rounded-xl bg-[#0C4B05] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a3d04]"
                    >
                        Go to Projects
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>