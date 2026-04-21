import type { User } from './user'
import type { Project } from './project'

export interface Panelist {
  id?: number
  name: string
  department: string
}

export interface Schedule {
  id: number

  defense_date: string
  defense_time: string
  venue?: string | null

  status?: 'pending' | 'confirmed' | 'completed'

  panelists: Panelist[]

  project?: Project

  confirmed?: boolean

  reschedule?: {
    requested: boolean
    status?: 'pending' | 'approved' | 'rejected'
  }
}