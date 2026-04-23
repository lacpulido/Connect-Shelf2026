import { usePage } from '@inertiajs/vue3'

export interface NotificationItem {
    id: number | string
    title: string
    message: string
    created_at: string
    status: string
    type?: string | null
    reference_id?: number | string | null
    reference_type?: string | null
}

export function useNotificationRedirect() {
    const page = usePage<any>()

    const getRedirectUrl = (notif: NotificationItem): string => {
        const type = notif.type ?? ''
        const referenceType = notif.reference_type ?? ''
        const referenceId = notif.reference_id ?? null
        const user = page.props.auth?.user ?? null

        if (
            (type === 'PAPER_SUBMITTED' || type === 'PAPER_RESUBMITTED') &&
            referenceType === 'project_document' &&
            referenceId
        ) {
            return `/faculty/documents/view/${referenceId}`
        }

        if (type === 'focal_assignment' || referenceType === 'focal_person') {
            return '/focal-person/projects'
        }

        if (user?.user_type === 2 || user?.role === 'student') {
            return '/student/submissions'
        }

        if (user?.user_type === 1 || user?.role === 'faculty') {
            if (
                type === 'schedule' ||
                type === 'schedule_response' ||
                referenceType === 'schedule'
            ) {
                return '/faculty/schedules'
            }

            return '/faculty/projects'
        }

        if (
            user?.role === 'department_chair' ||
            user?.role === 'Department ChairPerson' ||
            user?.role === 'Department Chairperson'
        ) {
            return '/department-chair/research-archives'
        }

        if (user?.role === 'focal_person' || user?.role === 'Focal Person') {
            return '/focal-person/projects'
        }

        if (user?.role === 'admin') {
            return '/admin'
        }

        return '/'
    }

    return {
        getRedirectUrl,
    }
}