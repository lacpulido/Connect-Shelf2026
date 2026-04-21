export function useDateFormatter() {
    const formatDateTime = (date?: string | null, fallback = 'N/A') => {
        if (!date) return fallback

        const d = new Date(date)
        if (Number.isNaN(d.getTime())) return fallback

        return d.toLocaleString('en-US', {
            month: 'long',
            day: '2-digit',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        })
    }

    return {
        formatDateTime,
    }
}