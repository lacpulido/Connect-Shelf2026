export type Role = {
    id: number
    name: string
}

export type Department = {
    id: number
    name: string
}

export type User = {
    id: number
    first_name: string
    last_name: string
    email: string
    user_type: 'faculty' | 'student' | null
    is_active: boolean
    status?: 'Active' | 'Inactive' | null
    department?: Department | null
    roles?: Role[]
    expertise?: string | null
}

export type AdminUsersProps = {
    users: User[]
    departments: string[]
    filters: {
        department?: string
        user_type?: string
    }
    chairRoleId: number
    departmentsWithChair: number[]
}