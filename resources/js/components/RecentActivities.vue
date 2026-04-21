<script setup lang="ts">
import { computed } from 'vue'

interface Activity {
  id: string
  type: string
  title: string
  description?: string
  created_at: string
  project_title?: string
}

const props = withDefaults(defineProps<{
  activities?: Activity[]
}>(), {
  activities: () => []
})

const normalizeType = (type: string) => {
  switch (type?.toLowerCase()) {
    case 'submission':
    case 'submitted':
      return 'submission'

    case 'comment':
    case 'under_review':
    case 'review':
      return 'comment'

    case 'approved':
    case 'completed':
      return 'approved'

    case 'revision':
    case 'needs_revision':
      return 'revision'

    default:
      return 'submission'
  }
}

const recentActivities = computed(() => {
  return [...props.activities]
    .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    .slice(0, 3)
})

const formatDate = (date: string) => {
  const d = new Date(date)
  return d.toLocaleString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  })
}

const getColor = (type: string) => {
  switch (type) {
    case 'submission':
      return 'border-blue-500 text-blue-600'
    case 'comment':
      return 'border-purple-500 text-purple-600'
    case 'approved':
      return 'border-green-500 text-green-600'
    case 'revision':
      return 'border-red-500 text-red-600'
    default:
      return 'border-gray-300 text-gray-500'
  }
}

const getLabel = (type: string) => {
  switch (type) {
    case 'submission':
      return 'Submitted'
    case 'comment':
      return 'Under Review'
    case 'approved':
      return 'Approved'
    case 'revision':
      return 'Needs Revision'
    default:
      return 'Activity'
  }
}
</script>

<template>
  <div class="rounded-2xl border border-gray-200 bg-white">
    <div class="border-b px-6 py-4">
      <h2 class="text-lg font-semibold text-gray-800">
        Recent Activities
      </h2>
    </div>

    <div
      v-if="recentActivities.length === 0"
      class="p-8 text-center text-gray-500"
    >
      No recent activities
    </div>

    <div v-else class="divide-y">
      <div
        v-for="activity in recentActivities"
        :key="activity.id"
        class="px-6 py-6"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0 flex-1">
            <p class="text-[15px] font-semibold text-gray-800">
              {{ activity.title }}
            </p>

            <p
              v-if="activity.project_title"
              class="mt-1 text-sm font-medium text-[#0C4B05]"
            >
              Project: {{ activity.project_title }}
            </p>
          </div>

          <span
            class="shrink-0 rounded-full border px-3 py-1 text-xs font-medium"
            :class="getColor(normalizeType(activity.type))"
          >
            {{ getLabel(normalizeType(activity.type)) }}
          </span>
        </div>

        <p
          v-if="activity.description"
          class="mt-2 text-sm text-gray-600"
        >
          {{ activity.description }}
        </p>

        <p class="mt-2 text-xs text-gray-400">
          {{ formatDate(activity.created_at) }}
        </p>
      </div>
    </div>
  </div>
</template>