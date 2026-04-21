import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { PageProps } from '@/types'

export function usePageData<T>(key: string, defaultValue: T) {
    const page = usePage<PageProps>()

    return computed<T>(() => (page.props[key] as T) ?? defaultValue)
}