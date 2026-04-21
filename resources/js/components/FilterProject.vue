<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps<{
    years: string[]
    projectTypes: string[]
    filters: {
        year: string
        project_type: string
    }
}>()

const selectedYear = ref(props.filters?.year || '')
const selectedType = ref(props.filters?.project_type || '')
watch([selectedYear, selectedType], () => {
    router.get(
        '/',
        {
            year: selectedYear.value,
            project_type: selectedType.value,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        }
    )
})
</script>

<template>
    <div class="w-full max-w-4xl flex flex-wrap gap-4 mb-6">
        <!-- Academic Year -->
        <select v-model="selectedYear" class="px-4 py-2 border rounded-lg">
            <option value="">All Academic Years</option>
            <option
                v-for="year in years"
                :key="year"
                :value="year"
            >
                {{ year }}
            </option>
        </select>

        <!-- Project Type -->
        <select v-model="selectedType" class="px-4 py-2 border rounded-lg">
            <option value="">All Project Types</option>
            <option
                v-for="type in projectTypes"
                :key="type"
                :value="type"
            >
                {{ type }}
            </option>
        </select>
    </div>
</template>