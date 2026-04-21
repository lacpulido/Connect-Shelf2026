import { onMounted, ref, watch, type Ref } from 'vue'

type SectionDefinition = {
    key: string
    label: string
}

export function useSubmissionAccordion(
    sections: SectionDefinition[],
    pageUrl: Ref<string>,
    documents?: Ref<unknown>,
) {
    const defaultOpenSections = () =>
        Object.fromEntries(sections.map((section) => [section.key, false])) as Record<string, boolean>

    const openSections = ref<Record<string, boolean>>(defaultOpenSections())

    const ensureSectionKeysExist = () => {
        const defaults = defaultOpenSections()

        for (const key in defaults) {
            if (!(key in openSections.value)) {
                openSections.value[key] = defaults[key]
            }
        }
    }

    const toggleSection = (key: string) => {
        openSections.value[key] = !openSections.value[key]
    }

    const isOpen = (key: string) => !!openSections.value[key]

    const setOnlyOneOpen = (key: string) => {
        const state = defaultOpenSections()

        if (key in state) {
            state[key] = true
        }

        openSections.value = state
    }

    const getOpenSectionFromUrl = () => {
        return new URLSearchParams(window.location.search).get('open')
    }

    const applyOpenSectionFromUrl = () => {
        const openKey = getOpenSectionFromUrl()

        ensureSectionKeysExist()

        if (openKey && openKey in openSections.value) {
            setOnlyOneOpen(openKey)
        }
    }

    onMounted(() => {
        applyOpenSectionFromUrl()
    })

    watch(pageUrl, () => {
        applyOpenSectionFromUrl()
    })

    if (documents) {
        watch(
            documents,
            () => {
                ensureSectionKeysExist()
            },
            { deep: true },
        )
    }

    return {
        openSections,
        toggleSection,
        isOpen,
        setOnlyOneOpen,
        applyOpenSectionFromUrl,
        ensureSectionKeysExist,
    }
}