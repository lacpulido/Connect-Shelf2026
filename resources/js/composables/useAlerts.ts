import Swal from 'sweetalert2'

const baseCustomClass = {
    popup: 'rounded-xl',
    title: 'text-lg font-semibold',
    htmlContainer: 'text-sm',
    confirmButton: 'px-4 py-1.5 text-sm rounded-md',
    cancelButton: 'px-4 py-1.5 text-sm rounded-md',
}

export function useAlerts() {
    const showSuccessAlert = (title: string, text: string) => {
        return Swal.fire({
            icon: 'success',
            title,
            text,
            confirmButtonColor: '#0C4B05',
            confirmButtonText: 'OK',
            width: 320,
            padding: '1rem',
            iconColor: '#16a34a',
            customClass: {
                ...baseCustomClass,
            },
        })
    }

    const showErrorAlert = (title: string, text: string) => {
        return Swal.fire({
            icon: 'error',
            title,
            text,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'OK',
            width: 320,
            padding: '1rem',
            customClass: {
                ...baseCustomClass,
            },
        })
    }

    const showInfoAlert = (title: string, text: string) => {
        return Swal.fire({
            icon: 'info',
            title,
            text,
            confirmButtonColor: '#2563eb',
            confirmButtonText: 'OK',
            width: 320,
            padding: '1rem',
            customClass: {
                ...baseCustomClass,
            },
        })
    }

    const showWarningAlert = (title: string, text: string) => {
        return Swal.fire({
            icon: 'warning',
            title,
            text,
            confirmButtonColor: '#d97706',
            confirmButtonText: 'OK',
            width: 320,
            padding: '1rem',
            customClass: {
                ...baseCustomClass,
            },
        })
    }

    const confirmAction = (
        title: string,
        text: string,
        confirmButtonText = 'Confirm',
        cancelButtonText = 'Cancel',
    ) => {
        return Swal.fire({
            icon: 'warning',
            title,
            text,
            showCancelButton: true,
            confirmButtonColor: '#0C4B05',
            cancelButtonColor: '#6b7280',
            confirmButtonText,
            cancelButtonText,
            width: 360,
            padding: '1rem',
            reverseButtons: true,
            customClass: {
                ...baseCustomClass,
            },
        })
    }

    const confirmDelete = (
        title = 'Delete?',
        text = 'This action cannot be undone.',
    ) => {
        return Swal.fire({
            icon: 'warning',
            title,
            text,
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            width: 360,
            padding: '1rem',
            reverseButtons: true,
            customClass: {
                ...baseCustomClass,
            },
        })
    }

    const showLoadingAlert = (title = 'Processing...') => {
        return Swal.fire({
            title,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            width: 260,
            padding: '1rem',
            customClass: {
                popup: 'rounded-xl',
                title: 'text-sm font-medium text-gray-700',
            },
            didOpen: () => {
                Swal.showLoading()
            },
        })
    }

    const closeAlert = () => Swal.close()

    return {
        showSuccessAlert,
        showErrorAlert,
        showInfoAlert,
        showWarningAlert,
        confirmAction,
        confirmDelete,
        showLoadingAlert,
        closeAlert,
    }
}