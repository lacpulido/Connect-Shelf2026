<script lang="ts" setup>
import NotificationBell from '@/components/NotificationBell.vue'
import SearchButton from '@/components/AdminSearchIcon.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { HTMLAttributes } from 'vue'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)

const isAdmin = computed(() => {
  return user.value?.roles?.some(
    (role: any) => role.name === 'Administrator'
  )
})

const openSearch = () => {
  console.log('Search clicked')
}
</script>

<template>
  <nav
    aria-label="breadcrumb"
    :class="['flex items-center w-full', props.class]"
  >
    <slot />

    <div class="ml-auto flex items-center gap-3">
      <button
        v-if="isAdmin"
        @click="openSearch"
        class="flex h-9 w-9 items-center justify-center rounded-md transition hover:bg-gray-200"
        type="button"
      >
        <SearchButton class="h-5 w-5" />
      </button>

      <NotificationBell />
    </div>
  </nav>
</template>