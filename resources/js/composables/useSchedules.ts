import { usePageData } from './usePageData'
import type { Schedule } from '@/types'

export function useSchedules() {
    return usePageData<Schedule[]>('schedules', [])
}