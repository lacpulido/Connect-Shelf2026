<script setup lang="ts">
import { computed } from 'vue'

type Variant =
    | 'default'
    | 'success'
    | 'warning'
    | 'danger'
    | 'info'
    | 'muted'
    | 'light'

const props = withDefaults(
    defineProps<{
        variant?: Variant
    }>(),
    {
        variant: 'default',
    },
)

const badgeClass = computed(() => {
    switch (props.variant) {
        case 'success':
            return 'border border-green-200 bg-green-100 text-green-700'
        case 'warning':
            return 'border border-amber-200 bg-amber-100 text-amber-800'
        case 'danger':
            return 'border border-red-200 bg-red-100 text-red-700'
        case 'info':
            return 'border border-blue-200 bg-blue-100 text-blue-700'
        case 'muted':
            return 'border border-gray-200 bg-gray-100 text-gray-600'
        case 'light':
            return 'border border-white/20 bg-white/20 text-white'
        default:
            return 'border border-gray-300 bg-gray-50 text-gray-700'
    }
})
</script>

<template>
    <span
        :class="[
            'inline-flex shrink-0 rounded-full px-3 py-1 text-xs font-semibold',
            badgeClass,
        ]"
    >
        <slot />
    </span>
</template>