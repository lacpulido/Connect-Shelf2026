<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

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

type Panelist = {
    id: number
    first_name: string
    last_name: string
    email?: string | null
    department?: Department | null
}

type ProjectData = {
    id: number
    adviser?: {
        id: number
    } | null
    panelists?: Panelist[]
}

const props = defineProps<{
    show: boolean
    project: ProjectData | null
    faculties: Faculty[]
    departments: Department[]
    filters?: {
        department_id?: number | null
    }
}>()

const emit = defineEmits<{
    (e: 'added', faculty: Faculty): void
    (e: 'close'): void
}>()

const selectedPanelist = ref<number | null>(null)
const showDropdown = ref(false)
const isSubmitting = ref(false)
const selectedDepartmentId = ref<number | ''>(props.filters?.department_id ?? '')
const errorMessage = ref('')
const localPanelists = ref<Panelist[]>([])

watch(
    () => props.project,
    (project) => {
        localPanelists.value = project?.panelists ? [...project.panelists] : []
    },
    { immediate: true, deep: true },
)

const panelists = computed(() => localPanelists.value)
const hasReachedMaximum = computed(() => panelists.value.length >= 3)

const availableFaculties = computed(() => {
    return props.faculties.filter((faculty) => {
        const isAlreadyPanelist = panelists.value.some((p) => p.id === faculty.id)
        const isAdviser = props.project?.adviser?.id === faculty.id

        const facultyDepartmentId = faculty.department_id ?? faculty.department?.id ?? null

        if (selectedDepartmentId.value === '') {
            return !isAlreadyPanelist && !isAdviser
        }

        return (
            !isAlreadyPanelist &&
            !isAdviser &&
            facultyDepartmentId === Number(selectedDepartmentId.value)
        )
    })
})

const selectedPanelistData = computed(() => {
    return props.faculties.find((f) => f.id === selectedPanelist.value) || null
})

const selectFaculty = (faculty: Faculty) => {
    if (hasReachedMaximum.value) return
    selectedPanelist.value = faculty.id
    showDropdown.value = false
    errorMessage.value = ''
}

const assignPanelist = () => {
    if (!props.project?.id || !selectedPanelist.value || isSubmitting.value || hasReachedMaximum.value) {
        return
    }

    const faculty = props.faculties.find((f) => f.id === selectedPanelist.value)
    errorMessage.value = ''
    isSubmitting.value = true

    router.post(
        route('focalperson.panelists.store'),
        {
            project_id: props.project.id,
            faculty_id: selectedPanelist.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (faculty) {
                    const alreadyExists = localPanelists.value.some((p) => p.id === faculty.id)

                    if (!alreadyExists) {
                        localPanelists.value.push({
                            id: faculty.id,
                            first_name: faculty.first_name,
                            last_name: faculty.last_name,
                            email: faculty.email,
                            department: faculty.department ?? null,
                        })
                    }

                    emit('added', faculty)
                }

                selectedPanelist.value = null
                showDropdown.value = false
                selectedDepartmentId.value = props.filters?.department_id ?? ''
            },
            onError: (errors) => {
                const firstError =
                    errors?.faculty_id ||
                    Object.values(errors || {})[0] ||
                    'Failed to assign panelist.'

                errorMessage.value = Array.isArray(firstError)
                    ? String(firstError[0])
                    : String(firstError)
            },
            onFinish: () => {
                isSubmitting.value = false
            },
        },
    )
}

watch(
    () => props.show,
    (value) => {
        if (!value) {
            selectedPanelist.value = null
            showDropdown.value = false
            isSubmitting.value = false
            errorMessage.value = ''
            selectedDepartmentId.value = props.filters?.department_id ?? ''
        }
    },
)

watch(
    () => props.filters?.department_id,
    (value) => {
        selectedDepartmentId.value = value ?? ''
    },
)
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 px-4 pt-14 pb-6 backdrop-blur-sm"
        @click.self="$emit('close')"
    >
        <div class="w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h2 class="text-xl font-bold text-[#0C4B05]">Assign Panelists</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Select faculty members and assign them to the panel.
                    </p>
                </div>

                <button
                    type="button"
                    @click="$emit('close')"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                >
                    ✕
                </button>
            </div>

            <div class="max-h-[78vh] overflow-y-auto px-6 py-5">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-800">
                                Select Faculty
                            </h3>
                            <p class="mt-1 text-xs text-gray-500">
                                Choose a department and assign an available faculty member.
                            </p>
                        </div>

                        <div v-if="!hasReachedMaximum" class="space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">
                                    Filter by Department
                                </label>
                                <select
                                    v-model="selectedDepartmentId"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-700 shadow-sm focus:border-[#0C4B05] focus:outline-none focus:ring-4 focus:ring-[#0C4B05]/10"
                                >
                                    <option value="">All Departments</option>
                                    <option
                                        v-for="department in departments"
                                        :key="department.id"
                                        :value="department.id"
                                    >
                                        {{ department.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="relative">
                                <label class="mb-2 block text-sm font-semibold text-gray-700">
                                    Select Faculty
                                </label>

                                <button
                                    type="button"
                                    @click="showDropdown = !showDropdown"
                                    class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-3 text-left text-sm text-gray-700 shadow-sm transition hover:border-gray-400 focus:outline-none focus:ring-4 focus:ring-[#0C4B05]/10"
                                >
                                    <span v-if="selectedPanelistData" class="truncate pr-3">
                                        <span class="font-semibold text-gray-800">
                                            {{ selectedPanelistData.first_name }}
                                            {{ selectedPanelistData.last_name }}
                                        </span>
                                        <span class="ml-1 text-gray-500">
                                            — {{ selectedPanelistData.email }}
                                        </span>
                                    </span>
                                    <span v-else class="text-gray-400">
                                        Choose a faculty member
                                    </span>

                                    <svg
                                        class="h-4 w-4 shrink-0 text-gray-500"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M19 9l-7 7-7-7"
                                        />
                                    </svg>
                                </button>

                                <div
                                    v-if="showDropdown"
                                    class="mt-3 max-h-72 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                                >
                                    <div
                                        v-for="faculty in availableFaculties"
                                        :key="faculty.id"
                                        @click="selectFaculty(faculty)"
                                        class="cursor-pointer border-b border-gray-100 px-4 py-3 transition last:border-b-0 hover:bg-[#0C4B05]/5"
                                    >
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ faculty.first_name }} {{ faculty.last_name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ faculty.email }}
                                        </p>
                                        <p
                                            v-if="faculty.department?.name"
                                            class="mt-2 inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-[11px] text-gray-600"
                                        >
                                            {{ faculty.department.name }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="!availableFaculties.length"
                                        class="px-4 py-6 text-center text-sm text-gray-400"
                                    >
                                        No available faculty found.
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="errorMessage"
                                class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600"
                            >
                                {{ errorMessage }}
                            </div>

                            <button
                                type="button"
                                @click="assignPanelist"
                                :disabled="!selectedPanelist || isSubmitting || hasReachedMaximum"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-[#FFCD00] px-6 py-3 text-sm font-bold text-[#0C4B05] shadow-sm transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{ isSubmitting ? 'Adding...' : 'Add Panelist' }}
                            </button>
                        </div>

                        <div
                            v-else
                            class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm font-medium text-amber-700"
                        >
                            Maximum of 3 panelists reached.
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                        <div class="mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Current Panelists
                                </h3>
                                <p class="mt-1 text-xs text-gray-500">
                                    Assigned faculty members for this project.
                                </p>
                            </div>
                        </div>

                        <div v-if="panelists.length" class="space-y-3">
                            <div
                                v-for="p in panelists"
                                :key="p.id"
                                class="rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ p.first_name }} {{ p.last_name }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Panel Member
                                        </p>

                                        <p
                                            v-if="p.department?.name"
                                            class="mt-2 inline-flex rounded-full bg-[#0C4B05]/10 px-2.5 py-1 text-[11px] font-medium text-[#0C4B05]"
                                        >
                                            {{ p.department.name }}
                                        </p>
                                    </div>

                                    <div
                                        class="rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-semibold text-green-700"
                                    >
                                        Assigned
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-8 text-center text-sm text-gray-400"
                        >
                            No panelists assigned yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>