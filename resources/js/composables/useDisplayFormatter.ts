type PersonLike = {
    first_name?: string | null
    last_name?: string | null
    department?: { name?: string | null } | string | null
}

export function useDisplayFormatters() {
    const fullName = (person?: PersonLike | null, fallback = 'Not Assigned') => {
        if (!person) return fallback

        const name = `${person.first_name ?? ''} ${person.last_name ?? ''}`.trim()
        return name || fallback
    }

    const fullNames = (people?: PersonLike[] | null, fallback = 'None') => {
        if (!people?.length) return fallback
        return people.map((person) => fullName(person)).join(', ')
    }

    const initials = (person?: PersonLike | null, fallback = 'NA') => {
        if (!person) return fallback

        const first = person.first_name?.charAt(0) ?? ''
        const last = person.last_name?.charAt(0) ?? ''
        const value = `${first}${last}`.trim().toUpperCase()

        return value || fallback
    }

    const departmentName = (person?: PersonLike | null, fallback = 'No department') => {
        if (!person?.department) return fallback

        if (typeof person.department === 'string') {
            return person.department || fallback
        }

        return person.department.name || fallback
    }

    const formatDate = (date?: string | null, fallback = 'N/A') => {
        if (!date) return fallback

        const parsed = new Date(date)
        if (Number.isNaN(parsed.getTime())) return date

        return parsed.toLocaleDateString(undefined, {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        })
    }

    const mysqlTimeToSlot = (time?: string | null) => {
        if (!time) return null

        const normalized = String(time).trim()

        if (['8-11', '11-2', '2-5'].includes(normalized)) return normalized
        if (normalized === '08:00:00' || normalized === '08:00') return '8-11'
        if (normalized === '11:00:00' || normalized === '11:00') return '11-2'
        if (normalized === '14:00:00' || normalized === '14:00') return '2-5'

        return normalized
    }

    const formatDefenseTime = (time?: string | null, fallback = 'N/A') => {
        const slot = mysqlTimeToSlot(time)

        if (slot === '8-11') return '8:00 AM - 11:00 AM'
        if (slot === '11-2') return '11:00 AM - 2:00 PM'
        if (slot === '2-5') return '2:00 PM - 5:00 PM'

        return time || fallback
    }

    return {
        fullName,
        fullNames,
        initials,
        departmentName,
        formatDate,
        mysqlTimeToSlot,
        formatDefenseTime,
    }
}