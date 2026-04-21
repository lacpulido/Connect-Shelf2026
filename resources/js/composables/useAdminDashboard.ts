import { usePage } from '@inertiajs/vue3'
import type { Activity } from '@/types'

interface AdminDashboardProps {
    totalUsers?: number
    totalProjects?: number
    ongoingProjects?: number
    completedProjects?: number
    activities?: Activity[]
}

export function useAdminDashboard() {
    const props = usePage<AdminDashboardProps>().props

    return {
        totalUsers: props.totalUsers ?? 0,
        totalProjects: props.totalProjects ?? 0,
        ongoingProjects: props.ongoingProjects ?? 0,
        completedProjects: props.completedProjects ?? 0,
        activities: props.activities ?? [],
    }
}