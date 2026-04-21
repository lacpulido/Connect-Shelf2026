<script setup lang="ts">
defineProps<{
    title: string
    description?: string
    minHeightClass?: string
}>()
</script>

<template>
    <div :class="['flex items-center justify-center', minHeightClass || 'min-h-[60vh]']">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white px-8 py-12 text-center shadow-sm">
            <div v-if="$slots.icon" class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#0C4B05]/10 text-[#0C4B05]">
                <slot name="icon" />
            </div>

            <h2 class="text-xl font-semibold text-gray-900">
                {{ title }}
            </h2>

            <p v-if="description" class="mt-2 text-sm text-gray-500">
                {{ description }}
            </p>

            <div v-if="$slots.action" class="mt-6">
                <slot name="action" />
            </div>
        </div>
    </div>
</template>