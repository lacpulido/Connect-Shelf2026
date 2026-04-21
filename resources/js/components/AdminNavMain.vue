<script setup>
import { Link, usePage } from '@inertiajs/vue3'

defineProps({
  items: Array,
})

const page = usePage()
</script>

<template>
  <div class="flex flex-col gap-3 px-3 py-2">

    <div v-for="item in items" :key="item.title">
      <Link
        :href="item.href"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg w-full transition-all duration-200"
        :class="{
          'bg-gray-200 font-semibold': page.url.startsWith(item.href),
          'hover:bg-gray-100': !page.url.startsWith(item.href)
        }"
      >
        <component
          :is="item.icon"
          v-if="item.icon"
          class="w-5 h-5"
        />

        <span class="text-sm">
          {{ item.title }}
        </span>
      </Link>
    </div>

  </div>
</template>
