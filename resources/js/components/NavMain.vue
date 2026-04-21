<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
})

const isActive = (item) => {
  if (!item.activeRoutes) return false

  return item.activeRoutes.some((routeName) =>
    route().current(routeName)
  )
}
</script>

<template>
  <div class="flex flex-col gap-7 px-3 mt-14">
    <div v-for="item in items" :key="item.title">
      <Link
        :href="item.href"
        preserve-scroll
        class="group flex items-center gap-3 rounded-xl h-12 px-3 transition-all duration-200"
        :class="{
          // ✅ ACTIVE = GRAY
          'bg-gray-200 text-gray-900 group-data-[state=collapsed]:bg-transparent': isActive(item),

          // ✅ HOVER = LIGHT GRAY
          'hover:bg-gray-100 group-data-[state=collapsed]:hover:bg-transparent': !isActive(item)
        }"
      >
        <!-- ICON -->
        <div class="flex items-center justify-center w-10 h-10">
          <component
            :is="item.icon"
            class="w-6 h-6 transition-colors duration-200"
            :class="isActive(item) ? 'text-gray-900' : 'text-gray-600'"
          />
        </div>

        <!-- TEXT -->
        <span class="group-data-[state=collapsed]:hidden text-lg tracking-wide">
          {{ item.title }}
        </span>
      </Link>
    </div>
  </div>
</template>