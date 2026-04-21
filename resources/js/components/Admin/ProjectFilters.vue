<script setup lang="ts">
/* ================= PROPS ================= */
defineProps<{
  academicYears: string[]
  projectTypes: string[]
  departments: string[]
  selectedYear: string | null
  selectedType: string | null
  selectedDepartment: string | null
}>()

/* ================= EMITS (STRICT TYPED) ================= */
const emit = defineEmits<{
  (e: 'update:selectedYear', value: string | null): void
  (e: 'update:selectedType', value: string | null): void
  (e: 'update:selectedDepartment', value: string | null): void
}>()

/* ================= HANDLER ================= */
const handleChange = (
  event: Event,
  type: 'year' | 'type' | 'department'
) => {
  const target = event.target as HTMLSelectElement
  const value = target.value || null

  if (type === 'year') {
    emit('update:selectedYear', value)
  }

  if (type === 'type') {
    emit('update:selectedType', value)
  }

  if (type === 'department') {
    emit('update:selectedDepartment', value)
  }
}
</script>

<template>
  <div class="grid gap-4 md:grid-cols-3">
    
    <!-- Academic Year -->
    <select
      class="rounded-xl border border-gray-300 p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
      :value="selectedYear ?? ''"
      @change="handleChange($event, 'year')"
    >
      <option value="">All Academic Years</option>
      <option
        v-for="year in academicYears"
        :key="year"
        :value="year"
      >
        {{ year }}
      </option>
    </select>

    <!-- Project Type -->
    <select
      class="rounded-xl border border-gray-300 p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
      :value="selectedType ?? ''"
      @change="handleChange($event, 'type')"
    >
      <option value="">All Project Types</option>
      <option
        v-for="type in projectTypes"
        :key="type"
        :value="type"
      >
        {{ type }}
      </option>
    </select>

    <!-- Department -->
    <select
      class="rounded-xl border border-gray-300 p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
      :value="selectedDepartment ?? ''"
      @change="handleChange($event, 'department')"
    >
      <option value="">All Departments</option>
      <option
        v-for="dept in departments"
        :key="dept"
        :value="dept"
      >
        {{ dept }}
      </option>
    </select>

  </div>
</template>
