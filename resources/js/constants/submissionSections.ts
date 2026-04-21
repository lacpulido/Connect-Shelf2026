// resources/js/constants/submissionSections.ts

/**
 * Defines the structure of each submission section.
 */
export type SectionDefinition = {
    key: string;
    label: string;
    keywords: string[];
};

/**
 * Centralized section configuration used across the application.
 */
export const SECTION_DEFINITIONS: SectionDefinition[] = [
    {
        key: 'chapter1',
        label: 'Chapter 1',
        keywords: ['chapter 1', 'chapter1'],
    },
    {
        key: 'chapter2',
        label: 'Chapter 2',
        keywords: ['chapter 2', 'chapter2'],
    },
    {
        key: 'chapter3',
        label: 'Chapter 3',
        keywords: ['chapter 3', 'chapter3'],
    },
    {
        key: 'chapter4',
        label: 'Chapter 4',
        keywords: ['chapter 4', 'chapter4'],
    },
    {
        key: 'chapter5',
        label: 'Chapter 5',
        keywords: ['chapter 5', 'chapter5'],
    },
    {
        key: 'manuscript',
        label: 'Manuscript',
        keywords: ['manuscript', 'final manuscript'],
    },
];

/**
 * Determines the section key based on a document title.
 */
export function getSectionKeyFromTitle(title?: string | null): string | null {
    if (!title) return null;

    const normalized = title.toLowerCase();

    const matchedSection = SECTION_DEFINITIONS.find((section) =>
        section.keywords.some((keyword) =>
            normalized.includes(keyword.toLowerCase())
        )
    );

    return matchedSection?.key ?? null;
}