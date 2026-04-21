import { computed, type Ref } from 'vue'

interface Project {
  id: number
  title: string
  abstract: string
  project_type: string
  status: string
}

export function useStudentDashboard(projects: Ref<Project[]>) {
  const hasProjects = computed(() => projects.value.length > 0)

  return {
    hasProjects
  }
}