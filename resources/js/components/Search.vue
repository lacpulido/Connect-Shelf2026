<script setup>
import { ref, watch, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Search, FileText } from 'lucide-vue-next'

const page = usePage()

// STATE
const query = ref('')
const results = ref([])
const loading = ref(false)
const hasSearched = ref(false)
const selectedProject = ref(null)

const isAuthenticated = computed(() => {
    return !!page.props.auth?.user
})

const viewDetails = (project) => {
    selectedProject.value = project
    document.body.style.overflow = 'hidden'
}

const closeModal = () => {
    selectedProject.value = null
    document.body.style.overflow = ''
}

let timeout = null

watch(query, (value) => {
    clearTimeout(timeout)

    timeout = setTimeout(() => {
        if (!value || !value.trim()) {
            results.value = []
            hasSearched.value = false
            return
        }

        search(value.trim())
    }, 400)
})

const search = async (q) => {
    loading.value = true

    try {
        const response = await fetch(`/api/semantic-search?q=${encodeURIComponent(q)}`, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        })

        if (!response.ok) {
            throw new Error('Network error')
        }

        const data = await response.json()

        results.value = (data.data ?? []).map((item) => ({
            ...item,
            abstract: item.abstract || item.snippet || 'No abstract available.',
        }))

        hasSearched.value = true
    } catch (error) {
        console.error('SEARCH ERROR:', error)
        results.value = []
        hasSearched.value = true
    } finally {
        loading.value = false
    }
}

const startDownload = (downloadUrl) => {
    window.location.href = downloadUrl
}

const downloadFile = (project) => {
    if (!project.download_url) {
        alert('No downloadable file available.')
        return
    }

    if (!isAuthenticated.value) {
        const loginUrl = new URL('/login', window.location.origin)
        loginUrl.searchParams.set('download', project.download_url)
        window.location.href = loginUrl.toString()
        return
    }

    startDownload(project.download_url)
}
</script>

<template>
    <section class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8 lg:py-16">
        <div class="mx-auto max-w-7xl">
            <h1
                class="mb-3 text-center text-3xl font-bold leading-tight sm:text-4xl md:text-5xl lg:text-6xl"
            >
                <span style="color: #0C4B05;">Explore</span>
                <span style="color: #FFCD00;"> Projects</span>
            </h1>

            <p class="mb-8 text-center text-base text-gray-600 sm:mb-10 sm:text-lg md:text-xl">
                Search through thesis and capstone works
            </p>

            <div class="mx-auto mb-8 w-full max-w-xl sm:mb-10">
                <div class="relative">
                    <Search
                        class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400 sm:left-5"
                    />

                    <input
                        v-model="query"
                        type="text"
                        placeholder="Search research..."
                        class="w-full rounded-full border border-gray-200 py-3 pl-11 pr-4 text-sm shadow focus:outline-none focus:ring-2 focus:ring-[#FFCD00] sm:py-4 sm:pl-12 sm:pr-6 sm:text-base"
                    />
                </div>
            </div>

            <div v-if="loading" class="text-center text-sm text-gray-500 sm:text-base">
                Searching...
            </div>

            <div
                v-if="hasSearched && results.length === 0 && !loading"
                class="text-center text-sm text-gray-500 sm:text-base"
            >
                No results found.
            </div>

            <div
                v-if="results.length > 0"
                class="space-y-4 sm:space-y-5 lg:space-y-6"
            >
                <div
                    v-for="hit in results"
                    :key="hit.id"
                    class="group rounded-2xl border-2 border-gray-100 bg-white p-4 shadow-md transition-all duration-300 hover:border-[#FFCD00] hover:shadow-xl sm:p-6 lg:p-8"
                >
                    <div
                        class="mb-3 flex flex-wrap items-center gap-2 sm:gap-3"
                    >
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-[11px] font-medium sm:px-4 sm:text-xs',
                                (hit.project_type || '').toLowerCase() === 'thesis'
                                    ? 'bg-[#FFCD00] text-[#0C4B05]'
                                    : 'bg-[#0C4B05] text-white',
                            ]"
                        >
                            <FileText class="h-3.5 w-3.5" />
                            {{ (hit.project_type || '').toLowerCase() === 'thesis' ? 'Thesis' : 'Capstone' }}
                        </span>

                        <span
                            class="rounded-lg bg-gray-100 px-3 py-1.5 text-[11px] text-gray-700 sm:text-xs"
                        >
                            {{ hit.academic_year }}
                        </span>
                    </div>

                    <h3
                        class="mb-3 break-words text-xl font-semibold leading-snug text-gray-900 transition group-hover:text-[#0C4B05] sm:text-2xl"
                    >
                        {{ hit.title }}
                    </h3>

                    <p class="mb-4 break-words text-sm leading-relaxed text-gray-600 sm:text-base">
                        {{ hit.snippet }}
                    </p>

                    <div
                        class="flex justify-end border-t border-gray-100 pt-4"
                    >
                        <button
                            @click="viewDetails(hit)"
                            class="w-full rounded-xl bg-[#0C4B05] px-5 py-2.5 text-sm font-medium text-white shadow-md transition-all duration-300 hover:bg-[#0C4B05]/90 hover:shadow-lg sm:w-auto sm:px-6"
                        >
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="selectedProject"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3 sm:p-4"
        >
            <div
                class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white sm:rounded-3xl"
            >
                <div class="border-b p-4 sm:p-6">
                    <h2
                        class="break-words text-lg font-semibold leading-snug text-gray-800 sm:text-2xl"
                    >
                        {{ selectedProject.title }}
                    </h2>
                </div>

                <div class="flex-1 space-y-5 overflow-y-auto p-4 sm:space-y-6 sm:p-6">
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-[#0C4B05] sm:text-base">
                            Abstract
                        </h3>
                        <p
                            class="break-words text-justify text-sm leading-relaxed text-gray-700 sm:text-base"
                        >
                            {{ selectedProject.abstract }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button
                            @click="downloadFile(selectedProject)"
                            class="w-full rounded-xl bg-[#0C4B05] py-3 text-sm font-medium text-white transition hover:opacity-90 sm:flex-1 sm:text-base"
                        >
                            Download PDF
                        </button>

                        <button
                            @click="closeModal"
                            class="w-full rounded-xl bg-gray-200 px-6 py-3 text-sm font-medium transition hover:bg-gray-300 sm:w-auto sm:text-base"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>