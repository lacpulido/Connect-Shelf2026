<script setup lang="ts">
import { computed } from 'vue'

type Variant =
    | 'primary'
    | 'secondary'
    | 'danger'
    | 'success'
    | 'warning'
    | 'ghost'
    | 'disabled'

type Size = 'sm' | 'md' | 'lg'

const props = withDefaults(
    defineProps<{
        variant?: Variant
        size?: Size
        block?: boolean
        disabled?: boolean
        type?: 'button' | 'submit' | 'reset'
    }>(),
    {
        variant: 'primary',
        size: 'md',
        block: false,
        disabled: false,
        type: 'button',
    },
)

const baseClass =
    'inline-flex items-center justify-center rounded-xl font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50'

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-9 px-3 text-xs'
        case 'lg':
            return 'h-12 px-5 text-base'
        default:
            return 'h-11 px-4 text-sm'
    }
})

const variantClass = computed(() => {
    if (props.disabled || props.variant === 'disabled') {
        return 'border border-gray-200 bg-gray-100 text-gray-400'
    }

    switch (props.variant) {
        case 'secondary':
            return 'border border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50 focus:ring-gray-300'
        case 'danger':
            return 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-200'
        case 'success':
            return 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-200'
        case 'warning':
            return 'bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-200'
        case 'ghost':
            return 'bg-transparent text-gray-700 hover:bg-gray-100 focus:ring-gray-200'
        default:
            return 'bg-[#0C4B05] text-white hover:bg-[#083803] focus:ring-[#0C4B05]/30'
    }
})

const widthClass = computed(() => (props.block ? 'w-full' : ''))
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        :class="[baseClass, sizeClass, variantClass, widthClass]"
    >
        <slot />
    </button>
</template>