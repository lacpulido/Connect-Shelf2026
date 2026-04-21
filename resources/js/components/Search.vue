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
}

const closeModal = () => {
    selectedProject.value = null
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
    <section class="px-6 py-16">
        <div class="mx-auto max-w-7xl">
            <h1 class="mb-3 text-center text-6xl">
                <span style="color: #0C4B05;">Explore</span>
                <span style="color: #FFCD00;"> Projects</span>
            </h1>

            <p class="mb-10 text-center text-xl">
                Search through thesis and capstone works
            </p>

            <div class="mx-auto mb-10 w-full max-w-xl">
                <div class="relative">
                    <Search
                        class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"
                        :size="20"
                    />

                    <input
                        v-model="query"
                        type="text"
                        placeholder="Search research..."
                        class="w-full rounded-full border border-gray-200 py-4 pl-12 pr-6 shadow focus:outline-none focus:ring-2 focus:ring-[#FFCD00]"
                    />
                </div>
            </div>

            <div v-if="loading" class="text-center text-gray-500">
                Searching...
            </div>

            <div
                v-if="hasSearched && results.length === 0 && !loading"
                class="text-center text-gray-500"
            >
                No results found.
            </div>

            <div v-if="results.length > 0" class="space-y-6">
                <div
                    v-for="hit in results"
                    :key="hit.id"
                    class="group rounded-2xl border-2 border-gray-100 bg-white p-8 shadow-md transition-all duration-300 hover:border-[#FFCD00] hover:shadow-xl"
                >
                    <div class="mb-3 flex items-center gap-3">
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-xl px-4 py-1.5 text-xs',
                                (hit.project_type || '').toLowerCase() === 'thesis'
                                    ? 'bg-[#FFCD00] text-[#0C4B05]'
                                    : 'bg-[#0C4B05] text-white',
                            ]"
                        >
                            <FileText class="h-3.5 w-3.5" />
                            {{ (hit.project_type || '').toLowerCase() === 'thesis' ? 'Thesis' : 'Capstone' }}
                        </span>

                        <span class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700">
                            {{ hit.academic_year }}
                        </span>
                    </div>

                    <h3 class="mb-3 text-2xl text-gray-900 transition group-hover:text-[#0C4B05]">
                        {{ hit.title }}
                    </h3>

                    <p class="mb-4 line-clamp-2 text-gray-600">
                        {{ hit.snippet }}
                    </p>

                    <div class="flex items-center justify-end border-t border-gray-100 pt-4">
                        <button
                            @click="viewDetails(hit)"
                            class="rounded-xl bg-[#0C4B05] px-6 py-2.5 text-white shadow-md transition-all duration-300 hover:bg-[#0C4B05]/90 hover:shadow-lg"
                        >
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="selectedProject"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white">
                <div class="border-b p-6">
                    <h2 class="text-2xl font-semibold text-gray-800">
                        {{ selectedProject.title }}
                    </h2>
                </div>

                <div class="space-y-6 p-6">
                    <div>
                        <h3 class="mb-3 font-semibold text-[#0C4B05]">Abstract</h3>
                        <p class="text-justify leading-relaxed text-gray-700">
                            {{ selectedProject.abstract }}
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button
                            @click="downloadFile(selectedProject)"
                            class="flex-1 rounded-xl bg-[#0C4B05] py-3 text-white transition hover:opacity-90"
                        >
                            Download PDF
                        </button>

                        <button
                            @click="closeModal"
                            class="rounded-xl bg-gray-200 px-6 py-3 transition hover:bg-gray-300"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>