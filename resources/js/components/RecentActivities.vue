<script setup lang="ts">
import { computed } from 'vue'

interface Activity {
  id: string | number
  type: string
  title: string
  description?: string | null
  created_at: string
  project_title?: string | null
}

const props = withDefaults(defineProps<{
  activities?: Activity[]
}>(), {
  activities: () => [],
})

const normalizeType = (type: string) => {
  switch (String(type ?? '').toLowerCase()) {
    case 'submission':
    case 'submitted':
      return 'submission'

    case 'comment':
    case 'under_review':
    case 'review':
    case 'pending':
      return 'comment'

    case 'approved':
    case 'accepted':
    case 'completed':
    case 'ongoing':
      return 'approved'

    case 'revision':
    case 'needs_revision':
    case 'declined':
      return 'revision'

    default:
      return 'submission'
  }
}

const recentActivities = computed(() => {
  return [...props.activities]
    .filter((activity) => activity.created_at)
    .sort(
      (a, b) =>
        new Date(b.created_at).getTime() -
        new Date(a.created_at).getTime()
    )
    .slice(0, 2) // only display 1-2 recent activities
})

const formatDate = (date: string) => {
  const d = new Date(date)

  if (Number.isNaN(d.getTime())) {
    return 'N/A'
  }

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
      return 'border-blue-500 bg-blue-50 text-blue-600'

    case 'comment':
      return 'border-purple-500 bg-purple-50 text-purple-600'

    case 'approved':
      return 'border-green-500 bg-green-50 text-green-600'

    case 'revision':
      return 'border-red-500 bg-red-50 text-red-600'

    default:
      return 'border-gray-300 bg-gray-50 text-gray-500'
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
  <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
      <h2 class="text-lg font-semibold text-gray-800">
        Recent Activities
      </h2>
    </div>

    <div
      v-if="recentActivities.length === 0"
      class="p-8 text-center text-sm text-gray-500"
    >
      No recent activities
    </div>

    <div v-else class="divide-y divide-gray-100">
      <div
        v-for="activity in recentActivities"
        :key="activity.id"
        class="px-5 py-5 sm:px-6"
      >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0 flex-1">
            <p class="break-words text-[15px] font-semibold text-gray-800">
              {{ activity.title }}
            </p>

            <p
              v-if="activity.project_title"
              class="mt-1 break-words text-sm font-medium text-[#0C4B05]"
            >
              Project: {{ activity.project_title }}
            </p>
          </div>

          <span
            class="w-fit shrink-0 rounded-full border px-3 py-1 text-xs font-medium"
            :class="getColor(normalizeType(activity.type))"
          >
            {{ getLabel(normalizeType(activity.type)) }}
          </span>
        </div>

        <p
          v-if="activity.description"
          class="mt-2 break-words text-sm leading-6 text-gray-600"
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