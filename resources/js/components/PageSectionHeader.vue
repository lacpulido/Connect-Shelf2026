<script setup lang="ts">
defineProps<{
    title: string
    description?: string
}>()
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white px-6 py-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ title }}
                </h1>
                <p v-if="description" class="mt-1 text-sm text-gray-500">
                    {{ description }}
                </p>
            </div>

            <div v-if="$slots.actions">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>