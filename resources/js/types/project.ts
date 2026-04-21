import type { Department } from './common'
import type { User } from './user'

export interface ProjectSchedule {
    id?: number
    defense_date?: string | null
    defense_time?: string | null
    venue?: string | null

    status?: string | null
    is_confirmed?: boolean | number | null
    confirmation_status?: string | null

    reschedule_requested?: boolean | number | null
    is_reschedule_requested?: boolean | number | null
    request_reschedule?: boolean | number | null
    reschedule_status?: string | null
}

export interface Project {
    id: number
    slug?: string | null
    title: string
    description?: string | null
    abstract?: string | null
    keywords?: string | null

    project_type?: string | null
    academic_year?: string | null
    start_date?: string | null
    end_date?: string | null
    status?: string | null
    completed_at?: string | null

    college_id?: number | string | null
    department_id?: number | string | null
    department?: Department | null

    student?: User | null
    user?: User | null
    adviser?: User | null
    adviser_id?: number | null
    researchers?: User[] | null
    panelists?: User[] | null

    schedule?: ProjectSchedule | null
    schedules?: ProjectSchedule[] | null
}