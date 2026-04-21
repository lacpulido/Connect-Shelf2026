import type{Department,Role} from './common'


export interface User {
    id: number
    first_name: string
    last_name: string
    email?: string
    department?:Department | null
    user_type?: 'faculty' | 'student' | null
    roles?: Role[]
    is_active?:boolean
    status?:'Active'|'Inactive'|null
    expertise?: string |null 
}
export interface Researcher {
    id: number
    name: string
    email: string
}