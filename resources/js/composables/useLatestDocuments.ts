import { computed, type Ref } from 'vue'
import type { Submission } from '@/types/submission'

export function useLatestDocuments(
    documents: Ref<Submission[][]>,
    options?: { sortByNewest?: boolean },
) {
    return computed<Submission[]>(() => {
        if (!Array.isArray(documents.value)) return []

        let result = documents.value
            .map((group) => {
                if (!Array.isArray(group) || group.length === 0) return null

                return group.find((item) => item.is_current) ?? group[0]
            })
            .filter((item): item is Submission => Boolean(item))

        if (options?.sortByNewest) {
            result = [...result].sort(
                (a, b) =>
                    new Date(b.created_at).getTime() -
                    new Date(a.created_at).getTime(),
            )
        }

        return result
    })
}