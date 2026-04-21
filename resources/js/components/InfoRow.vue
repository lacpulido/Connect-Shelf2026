<script setup lang="ts">
defineProps<{
    label: string
    bordered?: boolean
}>()
</script>

<template>
    <div :class="['flex items-start justify-between gap-3', bordered !== false ? 'border-b border-gray-200 pb-2.5' : '']">
        <span class="text-xs font-medium text-gray-500">
            {{ label }}
        </span>

        <span class="max-w-[60%] break-words text-right text-xs font-semibold text-gray-800">
            <slot />
        </span>
    </div>
</template>