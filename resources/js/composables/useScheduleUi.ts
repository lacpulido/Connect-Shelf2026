export type ScheduleStatus =
    | 'pending'
    | 'confirmed'
    | 'reschedule_requested'
    | string
    | null
    | undefined

export function useScheduleUi() {
    const normalizeStatus = (status?: ScheduleStatus) =>
        String(status ?? '').toLowerCase().trim()

    const isConfirmed = (status?: ScheduleStatus) =>
        normalizeStatus(status) === 'confirmed'

    const isRescheduleRequested = (status?: ScheduleStatus) =>
        normalizeStatus(status) === 'reschedule_requested'

    const getScheduleStatusLabel = (status?: ScheduleStatus) => {
        const value = normalizeStatus(status)

        if (value === 'confirmed') return 'Confirmed'
        if (value === 'reschedule_requested') return 'Reschedule Requested'
        return 'Pending'
    }

    const getScheduleStatusBadgeClass = (status?: ScheduleStatus) => {
        const value = normalizeStatus(status)

        if (value === 'confirmed') return 'bg-green-100 text-green-700'
        if (value === 'reschedule_requested') return 'bg-red-100 text-red-700'
        return 'bg-yellow-100 text-yellow-700'
    }

    const getScheduleStatusCardClass = (status?: ScheduleStatus) => {
        const value = normalizeStatus(status)

        if (value === 'confirmed') return 'border-green-200 bg-green-50 text-green-700'
        if (value === 'reschedule_requested') return 'border-red-200 bg-red-50 text-red-700'
        return 'border-yellow-200 bg-yellow-50 text-yellow-700'
    }

    const getScheduleButtonText = (hasSchedule: boolean, status?: ScheduleStatus) => {
        const value = normalizeStatus(status)

        if (value === 'reschedule_requested') return 'Reschedule Requested'
        if (value === 'confirmed') return 'Confirmed Schedule'
        if (hasSchedule) return 'Reschedule'
        return 'Set Schedule'
    }

    const getScheduleButtonClass = (hasSchedule: boolean, status?: ScheduleStatus) => {
        const value = normalizeStatus(status)

        if (value === 'reschedule_requested') {
            return 'bg-red-600 text-white hover:bg-red-700'
        }

        if (value === 'confirmed') {
            return 'bg-emerald-600 text-white hover:bg-emerald-700'
        }

        if (hasSchedule) {
            return 'bg-amber-500 text-white hover:bg-amber-600'
        }

        return 'bg-[#0C4B05] text-white hover:bg-[#0a3f04]'
    }

    return {
        normalizeStatus,
        isConfirmed,
        isRescheduleRequested,
        getScheduleStatusLabel,
        getScheduleStatusBadgeClass,
        getScheduleStatusCardClass,
        getScheduleButtonText,
        getScheduleButtonClass,
    }
}