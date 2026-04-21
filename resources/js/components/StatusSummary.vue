<script setup lang="ts">
import { Clock, Send, CheckCircle, AlertCircle } from 'lucide-vue-next'

const props = defineProps<{
  submitted: number
  under_review: number
  needs_revision: number
  completed: number
}>()
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

    <!-- Submitted -->
    <div class="card">
      <div>
        <p class="text-2xl font-bold">{{ submitted }}</p>
        <p class="text-sm text-gray-500">Submitted</p>
      </div>
      <Send class="icon text-blue-600" />
    </div>

    <!-- Under Review -->
    <div class="card">
      <div>
        <p class="text-2xl font-bold">{{ under_review }}</p>
        <p class="text-sm text-gray-500">Under Review</p>
      </div>
      <Clock class="icon text-purple-600" />
    </div>

    <!-- Needs Revision -->
    <div class="card">
      <div>
        <p class="text-2xl font-bold">{{ needs_revision }}</p>
        <p class="text-sm text-gray-500">Needs Revision</p>
      </div>
      <AlertCircle class="icon text-orange-500" />
    </div>

    <!-- Completed -->
    <div class="card">
      <div>
        <p class="text-2xl font-bold">{{ completed }}</p>
        <p class="text-sm text-gray-500">Completed</p>
      </div>
      <CheckCircle class="icon text-green-600" />
    </div>

  </div>
  
</template>

<style scoped>
.card {
  @apply flex items-center justify-between rounded-2xl border bg-white p-5 shadow-sm transition hover:shadow-md;
}
.icon {
  @apply h-6 w-6;
}
</style>