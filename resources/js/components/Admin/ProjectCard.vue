<script setup lang="ts">
interface Department {
  name: string
}

interface Project {
  id: number
  submitted_by?: string
  department?: Department | null
  project_type?: string | null
  academic_year?: string | null
}

defineProps<{
  project: Project
}>()
</script>

<template>
  <div
    class="rounded-2xl border border-gray-200 bg-white p-6 shadow transition-all hover:shadow-lg"
  >
    <!-- User -->
    <h3 class="text-lg font-semibold text-gray-900">
      {{ project.submitted_by ?? 'Unknown User' }}
    </h3>

    <div class="mt-4 space-y-1 text-sm text-gray-600">
      <p>
        <span class="font-medium">Department:</span>
        {{ project.department?.name ?? 'N/A' }}
      </p>

      <p>
        <span class="font-medium">Project Type:</span>
        {{ project.project_type ?? 'N/A' }}
      </p>

      <p>
        <span class="font-medium">Academic Year:</span>
        {{ project.academic_year ?? 'N/A' }}
      </p>
    </div>
  </div>
</template>
