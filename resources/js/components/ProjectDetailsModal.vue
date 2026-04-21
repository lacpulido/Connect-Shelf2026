<script setup lang="ts">
type Department = {
    id: number
    name: string
}

type Faculty = {
    id: number
    first_name: string
    last_name: string
    email: string
    department_id?: number | null
    department?: Department | null
    roles?: unknown[]
}

type Researcher = {
    id: number
    first_name: string
    last_name: string
}

type Panelist = {
    id: number
    first_name: string
    last_name: string
    email?: string | null
    department?: Department | null
}

type Schedule = {
    defense_date?: string | null
    defense_time?: string | null
    venue?: string | null
}

type Project = {
    id: number
    title: string
    description?: string | null
    user?: {
        id: number
        first_name: string
        last_name: string
    } | null
    researchers?: Researcher[]
    panelists?: Panelist[]
    schedule?: Schedule | null
}

defineProps<{
    show: boolean
    project: Project | null
    faculties: Faculty[]
}>()

defineEmits<{
    (e: 'close'): void
}>()

const formatDate = (date: string | null | undefined) => {
    if (!date) return 'Not Scheduled'

    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-black/40">
        <div class="flex justify-center px-4 pt-10 pb-20">
            <div class="relative w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b px-6 py-4">
                    <h2 class="text-lg font-semibold text-[#0C4B05]">
                        Project Details
                    </h2>

                    <button
                        @click="$emit('close')"
                        class="text-xl font-bold text-gray-500 hover:text-gray-700"
                    >
                        ✕
                    </button>
                </div>

                <div class="max-h-[75vh] space-y-5 overflow-y-auto rounded-b-2xl px-6 py-5">
                    <h1 class="text-xl font-bold text-gray-800">
                        {{ project?.title }}
                    </h1>

                    <p class="text-sm leading-relaxed text-gray-600">
                        {{ project?.description || 'No description provided.' }}
                    </p>

                    <div>
                        <h3 class="mb-2 font-semibold text-[#0C4B05]">
                            Researchers
                        </h3>

                        <p class="text-sm text-gray-700">
                            <strong>Leader:</strong>
                            {{ project?.user?.first_name }} {{ project?.user?.last_name }}
                        </p>

                        <ul
                            v-if="project?.researchers && project.researchers.length"
                            class="mt-2 space-y-1 text-sm text-gray-600"
                        >
                            <li
                                v-for="member in project.researchers.filter((m) => m.id !== project?.user?.id)"
                                :key="member.id"
                            >
                                • {{ member.first_name }} {{ member.last_name }}
                            </li>
                        </ul>

                        <p
                            v-if="!project?.researchers || project.researchers.length <= 1"
                            class="mt-1 text-sm text-gray-400"
                        >
                            No researchers
                        </p>
                    </div>

                    <hr class="my-4 border-gray-200" />

                    <div>
                        <h3 class="mb-2 font-semibold text-[#0C4B05]">
                            Defense Schedule
                        </h3>

                        <div class="space-y-1 text-sm text-gray-600">
                            <p>
                                <strong>Date:</strong>
                                {{ formatDate(project?.schedule?.defense_date) }}
                            </p>

                            <p>
                                <strong>Time:</strong>
                                {{ project?.schedule?.defense_time ?? '-' }}
                            </p>

                            <p>
                                <strong>Venue:</strong>
                                {{ project?.schedule?.venue ?? 'TBA' }}
                            </p>
                        </div>
                    </div>

                    <hr class="my-4 border-gray-200" />

                    <div>
                        <h3 class="mb-2 font-semibold text-[#0C4B05]">
                            Panelists
                        </h3>

                        <div v-if="project?.panelists && project.panelists.length" class="space-y-2">
                            <div
                                v-for="p in project.panelists"
                                :key="p.id"
                                class="flex items-center justify-between rounded-md border px-3 py-2"
                            >
                                <div>
                                    <span class="text-sm text-gray-700">
                                        {{ p.first_name }} {{ p.last_name }}
                                    </span>

                                    <p
                                        v-if="p.department?.name"
                                        class="mt-1 text-xs text-gray-500"
                                    >
                                        {{ p.department.name }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <p v-else class="text-sm text-gray-400">
                            No panelists assigned
                        </p>
                    </div>

                    <div class="pt-3"></div>
                </div>
            </div>
        </div>
    </div>
</template>