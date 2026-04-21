<script setup lang="ts">
import { Search } from 'lucide-vue-next'


const emit = defineEmits<{
  (e: 'click'): void
}>()

const handleClick = () => {
  emit('click')
}
</script>

<template>
  <button
    @click="handleClick"
    class="flex items-center justify-center w-9 h-9 rounded-md hover:bg-gray-200 transition"
  >
    <Search class="w-6 h-6 text-gray-600" />
  </button>
</template>
