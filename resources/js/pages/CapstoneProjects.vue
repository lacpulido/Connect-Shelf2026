<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import Navbar from '@/components/Navbar.vue'
import { ref } from 'vue'
import { CirclePlus, CircleMinus, Archive } from 'lucide-vue-next'

defineProps<{
    projects: Array<any>
}>()

const activeItem = ref<number | null>(null)

let timeout: any = null

const setActive = (id: any) => {
    clearTimeout(timeout)
    activeItem.value = Number(id)
}

const clearActive = () => {
    timeout = setTimeout(() => {
        activeItem.value = null
    }, 120)
}

const isOpen = (id: any) => activeItem.value === Number(id)

const formatDate = (date: string | null) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}
</script>

<template>
    <Head title="Capstone Projects" />
    <Navbar />

    <div class="w-full max-w-6xl mx-auto px-6 pt-6 pb-10">
        
        <!-- TITLE -->
        <h1 class="text-3xl font-bold text-[#0C4B05]">
            Capstone Projects
        </h1>

        <p class="mt-2 text-sm text-gray-600">
            List of available capstone projects for public viewing.
        </p>

        <!-- EMPTY -->
        <div v-if="projects.length === 0" class="text-gray-500 text-sm mt-6">
            No capstone projects available.
        </div>

        <!-- LIST -->
        <div v-else class="divide-y mt-10">
            <div
                v-for="project in projects"
                :key="project.id"
                class="py-3"
                @mouseenter="setActive(project.id)"
                @mouseleave="clearActive"
            >
                <!-- HEADER -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Archive class="w-4 h-4 text-gray-500" />

                        <h2 class="text-sm font-medium text-black hover:text-[#0C4B05] transition">
                            {{ project.title }}
                        </h2>
                    </div>

                    <div class="w-7 h-7 flex items-center justify-center rounded-full bg-gray-200">
                        <CirclePlus v-if="!isOpen(project.id)" class="w-4 h-4" />
                        <CircleMinus v-else class="w-4 h-4" />
                    </div>
                </div>

                <!-- CONTENT -->
                <div
                    class="overflow-hidden transition-all duration-300 ease-in-out"
                    :class="isOpen(project.id)
                        ? 'max-h-96 opacity-100 translate-y-0 mt-3 pl-5'
                        : 'max-h-0 opacity-0 -translate-y-2'
                    "
                >
                    <!-- Department -->
                    <p class="text-xs text-gray-600 mb-1">
                        {{ project.department?.name }}
                    </p>

                    <!-- Abstract -->
                    <p class="text-xs text-gray-700 mb-3 leading-relaxed">
                        {{ project.abstract }}
                    </p>

                    <!-- Button -->
                    <a
                        :href="route('capstone.show', project.id)"
                        class="inline-block bg-[#0C4B05] hover:bg-[#093804] text-white text-xs px-3 py-1.5 rounded transition"
                    >
                        View Item Details
                    </a>

                    <!-- Manuscripts -->
                    <div class="mt-3">
                        <div v-if="project.manuscripts?.length">
                            <!-- Optional: loop manuscripts if needed -->
                        </div>

                        <div v-else class="text-xs text-gray-400">
                            No manuscripts available
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>