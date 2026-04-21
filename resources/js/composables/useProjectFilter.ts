import { computed, ref, type Ref } from 'vue'
import type { Project } from '@/types'

export function useProjectFilter(projects: Ref<Project[]>) {
    const selectedYear = ref<string | null>(null)
    const selectedType = ref<string | null>(null)
    const selectedDepartment = ref<string | null>(null)

    const academicYears = computed(() =>
        [...new Set(projects.value.map((p) => p.academic_year).filter(Boolean))] as string[]
    )

    const projectTypes = computed(() =>
        [...new Set(projects.value.map((p) => p.project_type).filter(Boolean))] as string[]
    )

    const departments = computed(() =>
        [...new Set(projects.value.map((p) => p.department?.name).filter(Boolean))] as string[]
    )

    const filteredProjects = computed(() => {
        return projects.value.filter((p) => {
            const matchesYear =
                !selectedYear.value || p.academic_year === selectedYear.value

            const matchesType =
                !selectedType.value || p.project_type === selectedType.value

            const matchesDepartment =
                !selectedDepartment.value || p.department?.name === selectedDepartment.value

            return matchesYear && matchesType && matchesDepartment
        })
    })

    return {
        selectedYear,
        selectedType,
        selectedDepartment,
        academicYears,
        projectTypes,
        departments,
        filteredProjects,
    }
}