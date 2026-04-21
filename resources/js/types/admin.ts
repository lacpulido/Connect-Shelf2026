import type { User } from './user'

export interface AdminUsersProps {
    users: User[]
    departments: string[]
    filters: {
        department?: string
        user_type?: string
    }
    chairRoleId: number
    departmentsWithChair: number[]
}