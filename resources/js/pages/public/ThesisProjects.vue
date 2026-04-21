<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Header from '@/components/Navbar.vue'
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination'

type Project = {
    id: number
    slug: string
    title: string
    abstract?: string | null
    academic_year?: string | null
}

type PaginatedProjects = {
    data: Project[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

const props = defineProps<{
    projects: PaginatedProjects
    years: Array<string>
    filters: {
        academic_year?: string | null
    }
}>()

const selectedYear = ref(props.filters?.academic_year || '')
const currentPage = ref(props.projects.current_page || 1)

watch(
    () => props.filters?.academic_year,
    (value) => {
        selectedYear.value = value || ''
    }
)

watch(
    () => props.projects.current_page,
    (value) => {
        currentPage.value = value || 1
    }
)

const filterProjects = () => {
    router.get(
        route('resources.thesis'),
        {
            academic_year: selectedYear.value || undefined,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

watch(currentPage, (page, oldPage) => {
    if (page === oldPage) return

    router.get(
        route('resources.thesis'),
        {
            academic_year: selectedYear.value || undefined,
            page,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
})
</script>

<template>
    <Head title="Thesis Projects" />
    <Header />

    <div class="mx-auto w-full max-w-6xl px-6 pb-10 pt-6">
        <div
            class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold text-[#0C4B05]">
                    Thesis Projects
                </h1>

                <p class="mt-1 text-gray-600">
                    List of available thesis projects.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">
                    Filter by:
                </label>

                <select
                    v-model="selectedYear"
                    @change="filterProjects"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 shadow-sm outline-none focus:border-[#0C4B05] focus:ring-2 focus:ring-[#0C4B05]/20"
                >
                    <option value="">All Academic Years</option>

                    <option
                        v-for="year in years"
                        :key="year"
                        :value="year"
                    >
                        {{ year }}
                    </option>
                </select>
            </div>
        </div>

        <div v-if="projects.data.length === 0" class="text-lg text-gray-500">
            No thesis projects found.
        </div>

        <div v-else>
            <div class="divide-y">
                <div
                    v-for="project in projects.data"
                    :key="project.id"
                    class="py-5"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-medium font-semibold text-black">
                                {{ project.title }}
                            </h2>
                        </div>

                        <a
                           :href="route('resources.thesis.show',{ slug: project.slug })"
                            class="shrink-0 text-sm font-medium text-[#0C4B05] hover:text-[#093804] hover:underline"
                        >
                            View Details →
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-4 md:flex-row md:items-center">
                <p class="text-sm text-gray-600">
                    Showing
                    <span class="font-medium">{{ projects.from ?? 0 }}</span>
                    to
                    <span class="font-medium">{{ projects.to ?? 0 }}</span>
                    of
                    <span class="font-medium">{{ projects.total }}</span>
                    projects
                </p>

                <div class="flex w-full justify-end md:ml-auto md:w-auto">
                    <Pagination
                        v-if="projects.last_page > 1"
                        v-model:page="currentPage"
                        :items-per-page="projects.per_page"
                        :total="projects.total"
                    >
                        <PaginationContent v-slot="{ items }">
                            <PaginationPrevious />

                            <template
                                v-for="(item, index) in items"
                                :key="index"
                            >
                                <PaginationItem
                                    v-if="item.type === 'page'"
                                    :value="item.value"
                                    :is-active="item.value === currentPage"
                                >
                                    {{ item.value }}
                                </PaginationItem>

                                <PaginationEllipsis
                                    v-else
                                    :index="index"
                                />
                            </template>

                            <PaginationNext />
                        </PaginationContent>
                    </Pagination>
                </div>
            </div>
        </div>
    </div>
</template>