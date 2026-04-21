import { computed, type Ref } from 'vue'
import type { Submission } from '@/types'
import type { SectionDefinition } from '@/constants/submissionSections'

export function useGroupedSections(
    latestDocuments: Ref<Submission[]>,
    sections: SectionDefinition[]
) {
    const groupedSections = computed(() =>
        sections.map((section) => ({
            key: section.key,
            label: section.label,
            documents: latestDocuments.value.filter((doc) => {
                const title = (doc.title ?? '').toLowerCase();
                const filename = (doc.filename ?? '').toLowerCase();

                return section.keywords.some((keyword) => {
                    const k = keyword.toLowerCase();
                    return title.includes(k) || filename.includes(k);
                });
            }),
        }))
    );

    return { groupedSections };
}