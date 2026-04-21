import { computed, type Ref } from 'vue'
import type { Project } from '@/types'

export function useStudentDashboard(projects: Ref<Project[]>) {
    const hasProjects = computed(() => projects.value.length > 0)

    return {
        hasProjects,
    }
}