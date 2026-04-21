<script setup lang="ts">
interface Props {
  class?: string
  src?: string
  alt?: string
}

withDefaults(defineProps<Props>(), {
  src: '/images/coonect.png',
  alt: 'Logo'
})
</script>

<template>
  <img
    :src="src"
    :alt="alt"
    :class="class"
    class="object-contain transition-all duration-300"
  />
</template>