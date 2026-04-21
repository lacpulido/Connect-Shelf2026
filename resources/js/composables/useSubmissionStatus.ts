export function useSubmissionStatus() {
    const statusClasses = (status?: string | null) => {
        switch (status) {
            case 'submitted':
                return 'border-blue-500 text-blue-600 bg-blue-50'
            case 'under_review':
                return 'border-purple-500 text-purple-600 bg-purple-50'
            case 'needs_revision':
            case 'revision_required':
                return 'border-red-500 text-red-600 bg-red-50'
            case 'approved':
                return 'border-green-500 text-green-600 bg-green-50'
            case 'rejected':
                return 'border-red-500 text-red-600 bg-red-50'
            default:
                return 'border-gray-300 text-gray-600 bg-gray-50'
        }
    }

    const statusLabel = (status?: string | null) => {
        switch (status) {
            case 'submitted':
                return 'Submitted'
            case 'under_review':
                return 'Under Review'
            case 'needs_revision':
            case 'revision_required':
                return 'Needs Revision'
            case 'approved':
                return 'Approved'
            case 'rejected':
                return 'Rejected'
            default:
                return 'Unknown'
        }
    }

    return { statusClasses, statusLabel }
}