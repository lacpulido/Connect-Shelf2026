<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import {
    Breadcrumb,
    BreadcrumbItem as CrumbItem,
    BreadcrumbList,
} from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar'
import { useAlerts } from '@/composables/useAlerts'
import { Head, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { LoaderCircle } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

type SearchUser = {
    id: number
    first_name: string
    last_name: string
    email: string
    user_type: number
    department_id?: number | null
}

const props = defineProps<{
    colleges: Array<{ id: number; name: string }>
    departments: Array<{ id: number; name: string; college_id: number }>
    authUser: {
        college_id: number
        department_id: number
    }
}>()

const { showSuccessAlert, showErrorAlert } = useAlerts()

const isSaving = ref(false)

const searchQuery = ref('')
const searchResults = ref<SearchUser[]>([])
const isSearching = ref(false)
let timeout: ReturnType<typeof setTimeout> | null = null

const adviserQuery = ref('')
const adviserResults = ref<SearchUser[]>([])
const isSearchingAdviser = ref(false)
let adviserTimeout: ReturnType<typeof setTimeout> | null = null

const getCurrentAcademicYear = () => {
    const now = new Date()
    const year = now.getFullYear()
    const month = now.getMonth() + 1

    return month >= 8 ? `${year}-${year + 1}` : `${year - 1}-${year}`
}

const getProjectTypeByDepartment = (
    departmentId: number | string | null,
) => {
    const department = props.departments.find(
        (d) => d.id === Number(departmentId),
    )

    if (!department) return ''

    const departmentName = department.name.trim().toLowerCase()

    if (departmentName === 'information technology') return 'Capstone'
    if (departmentName === 'computer science') return 'Thesis'

    return ''
}

const sanitizePlainText = (value: string) => {
    return value
        .replace(/<[^>]*>/g, '')
        .replace(/[\u0000-\u001F\u007F]/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, 50)
}

/**
 * Display-only values from authenticated user / server-side defaults.
 * These are shown in the UI, but not trusted by the backend.
 */
const displayCollegeName = computed(() => {
    return (
        props.colleges.find((c) => c.id === props.authUser.college_id)?.name ??
        ''
    )
})

const displayDepartmentName = computed(() => {
    return (
        props.departments.find((d) => d.id === props.authUser.department_id)
            ?.name ?? ''
    )
})

const displayAcademicYear = computed(() => getCurrentAcademicYear())
const displaySemester = computed(() => '1st Semester')

const displayProjectType = computed(() => {
    return getProjectTypeByDepartment(props.authUser.department_id)
})

/**
 * Only user-editable fields are kept in the form payload.
 * Server will supply the locked/default fields.
 */
const form = useForm({
    title: '',
    researchers: [] as Array<{ id: number; name: string; email: string }>,
    adviser: null as null | { id: number; name: string; email: string },
    adviser_id: null as number | null,
})

watch(
    () => form.title,
    (value) => {
        const sanitized = sanitizePlainText(value ?? '')

        if (sanitized !== value) {
            form.title = sanitized
        }
    },
)

watch(searchQuery, (value) => {
    if (timeout) clearTimeout(timeout)

    const keyword = value.trim()

    if (keyword.length < 3) {
        searchResults.value = []
        isSearching.value = false
        return
    }

    timeout = setTimeout(async () => {
        try {
            isSearching.value = true

            const response = await axios.get<SearchUser[]>(
                route('student.users.search'),
                {
                    params: {
                        q: keyword,
                        user_type: 2,
                        exclude: form.researchers.map((r) => r.id),
                    },
                },
            )

            searchResults.value = response.data.filter(
                (u) => !form.researchers.find((r) => r.id === u.id),
            )
        } catch (error) {
            console.error(error)
            searchResults.value = []
        } finally {
            isSearching.value = false
        }
    }, 400)
})

watch(adviserQuery, (value) => {
    if (adviserTimeout) clearTimeout(adviserTimeout)

    const keyword = value.trim()

    if (keyword.length < 3) {
        adviserResults.value = []
        isSearchingAdviser.value = false
        return
    }

    adviserTimeout = setTimeout(async () => {
        try {
            isSearchingAdviser.value = true

            const response = await axios.get<SearchUser[]>(
                route('student.users.search'),
                {
                    params: {
                        q: keyword,
                        user_type: 1,
                    },
                },
            )

            adviserResults.value = response.data
        } catch (error) {
            console.error(error)
            adviserResults.value = []
        } finally {
            isSearchingAdviser.value = false
        }
    }, 400)
})

const addResearcher = (user: SearchUser) => {
    if (form.researchers.length >= 4) return

    const exists = form.researchers.find((r) => r.id === user.id)

    if (!exists) {
        form.researchers.push({
            id: user.id,
            name: `${user.first_name} ${user.last_name}`,
            email: user.email,
        })
    }

    searchQuery.value = ''
    searchResults.value = []
}

const removeResearcher = (id: number) => {
    form.researchers = form.researchers.filter((r) => r.id !== id)
}

const selectAdviser = (user: SearchUser) => {
    form.adviser = {
        id: user.id,
        name: `${user.first_name} ${user.last_name}`,
        email: user.email,
    }

    form.adviser_id = user.id
    adviserQuery.value = ''
    adviserResults.value = []
}

const removeAdviser = () => {
    form.adviser = null
    form.adviser_id = null
}

const resetFormState = () => {
    form.reset()
    form.researchers = []
    form.adviser = null
    form.adviser_id = null
    searchQuery.value = ''
    adviserQuery.value = ''
    searchResults.value = []
    adviserResults.value = []
}

const submit = () => {
    isSaving.value = true

    setTimeout(() => {
        form.transform((data) => ({
            title: sanitizePlainText(data.title),
            researchers: data.researchers.map((r) => r.id),
            adviser_id: data.adviser ? data.adviser.id : null,
        })).post(route('student.projects.store'), {
            preserveScroll: true,
            onSuccess: async () => {
                resetFormState()

                await showSuccessAlert(
                    'Welcome to Your Dashboard! 🎉',
                    'Your project has been successfully created.',
                )

                window.location.href = route('student.dashboard')
            },
            onError: async () => {
                await showErrorAlert('Error', 'Something went wrong.')
                isSaving.value = false
            },
            onFinish: () => {
                if (Object.keys(form.errors).length > 0) {
                    isSaving.value = false
                }
            },
        })
    }, 3000)
}
</script>

<template>
    <Head title="Create Project" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-14 items-center gap-2 border-b px-4 md:px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem>Project</CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="p-3 md:p-5">
                <form
                    @submit.prevent="submit"
                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                >
                    <!-- Project Title -->
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Project Title <span class="text-red-600">*</span>
                        </label>

                        <input
                            v-model="form.title"
                            type="text"
                            maxlength="50"
                            class="w-full rounded-md border px-3 py-2 text-sm"
                        />

                        <div class="mt-1 flex items-center justify-between">
                            <div v-if="form.errors.title" class="text-xs text-red-600">
                                {{ form.errors.title }}
                            </div>
                            <div v-else></div>
                            <div class="text-xs text-gray-500">
                                {{ form.title.length }}/50
                            </div>
                        </div>
                    </div>

                    <!-- College -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            College
                        </label>
                        <input
                            :value="displayCollegeName"
                            disabled
                            class="w-full rounded-md border bg-gray-100 px-3 py-2 text-sm"
                        />
                    </div>

                    <!-- Department -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Department
                        </label>
                        <input
                            :value="displayDepartmentName"
                            disabled
                            class="w-full rounded-md border bg-gray-100 px-3 py-2 text-sm"
                        />
                    </div>

                    <!-- Academic Year -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Academic Year
                        </label>
                        <input
                            :value="displayAcademicYear"
                            disabled
                            class="w-full rounded-md border bg-gray-100 px-3 py-2 text-sm"
                        />
                    </div>

                    <!-- Semester -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Semester
                        </label>
                        <input
                            :value="displaySemester"
                            disabled
                            class="w-full rounded-md border bg-gray-100 px-3 py-2 text-sm"
                        />
                    </div>

                    <!-- Project Type -->
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Project Type
                        </label>
                        <input
                            :value="displayProjectType"
                            disabled
                            class="w-full rounded-md border bg-gray-100 px-3 py-2 text-sm"
                        />
                    </div>

                    <!-- Researchers -->
                    <div class="min-w-0">
                        <div class="flex h-full flex-col">
                            <div class="mb-3 min-h-[58px]">
                                <div class="flex items-start justify-between gap-3">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Researchers <span class="text-red-600">*</span>
                                    </label>

                                    <span class="text-xs text-gray-500">
                                        {{ form.researchers.length + 1 }}/5
                                    </span>
                                </div>

                                <p class="mt-1 text-xs text-gray-500">
                                    Maximum of 5 members including the leader
                                </p>
                            </div>

                            <div
                                class="mb-2 flex min-h-[80px] items-start rounded-md border border-dashed border-gray-200 bg-gray-50 px-3 py-3"
                            >
                                <div
                                    v-if="form.researchers.length"
                                    class="flex flex-wrap gap-1.5"
                                >
                                    <div
                                        v-for="r in form.researchers"
                                        :key="r.id"
                                        class="flex max-w-full items-center gap-1 rounded-full bg-[#0C4B05] px-2.5 py-1 text-[11px] text-white"
                                    >
                                        <span class="max-w-[140px] truncate md:max-w-[180px]">
                                            {{ r.name }}
                                        </span>

                                        <button
                                            type="button"
                                            @click="removeResearcher(r.id)"
                                            class="leading-none"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search student..."
                                :disabled="form.researchers.length >= 4"
                                class="w-full rounded-md border px-3 py-2 text-sm disabled:cursor-not-allowed disabled:bg-gray-100"
                            />

                            <div class="mt-1 min-h-[20px]">
                                <div
                                    v-if="form.researchers.length >= 4"
                                    class="text-xs text-amber-600"
                                >
                                    You already reached the maximum of 5 members.
                                </div>

                                <div
                                    v-else-if="form.errors.researchers"
                                    class="text-xs text-red-600"
                                >
                                    {{ form.errors.researchers }}
                                </div>

                                <div
                                    v-else-if="(form.errors as Record<string, string>)['researchers.0']"
                                    class="text-xs text-red-600"
                                >
                                    {{ (form.errors as Record<string, string>)['researchers.0'] }}
                                </div>
                            </div>

                            <div
                                v-if="searchResults.length && form.researchers.length < 4"
                                class="mt-1 max-h-60 w-full overflow-y-auto rounded-md border bg-white shadow-sm"
                            >
                                <div
                                    v-for="user in searchResults"
                                    :key="user.id"
                                    @click="addResearcher(user)"
                                    class="cursor-pointer px-3 py-2 hover:bg-gray-100"
                                >
                                    <div class="break-words text-sm font-medium">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </div>
                                    <div class="break-all text-[11px] text-gray-500">
                                        {{ user.email }}
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="isSearching && form.researchers.length < 4"
                                class="mt-1 text-[11px] text-gray-500"
                            >
                                Searching researchers...
                            </div>
                        </div>
                    </div>

                    <!-- Adviser -->
                    <div class="min-w-0">
                        <div class="flex h-full flex-col">
                            <div class="mb-3 min-h-[58px]">
                                <label class="block text-sm font-medium text-gray-700">
                                    Adviser <span class="text-red-600">*</span>
                                </label>

                                <p class="mt-1 select-none text-xs text-transparent">
                                    Maximum of 5 members including the leader
                                </p>
                            </div>

                            <div
                                class="mb-2 flex min-h-[80px] items-start rounded-md border border-dashed border-gray-200 bg-gray-50 px-3 py-3"
                            >
                                <div v-if="form.adviser" class="flex flex-wrap gap-1.5">
                                    <div
                                        class="flex max-w-full items-center gap-1 rounded-full bg-[#0C4B05] px-2.5 py-1 text-[11px] text-white"
                                    >
                                        <span class="max-w-[140px] truncate md:max-w-[180px]">
                                            {{ form.adviser.name }}
                                        </span>

                                        <button
                                            type="button"
                                            @click="removeAdviser"
                                            class="leading-none"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <input
                                v-model="adviserQuery"
                                type="text"
                                placeholder="Search faculty adviser..."
                                class="w-full rounded-md border px-3 py-2 text-sm"
                            />

                            <div class="mt-1 min-h-[20px]">
                                <div
                                    v-if="form.errors.adviser_id"
                                    class="text-xs text-red-600"
                                >
                                    {{ form.errors.adviser_id }}
                                </div>
                            </div>

                            <div
                                v-if="adviserResults.length"
                                class="mt-1 max-h-60 w-full overflow-y-auto rounded-md border bg-white shadow-sm"
                            >
                                <div
                                    v-for="user in adviserResults"
                                    :key="user.id"
                                    @click="selectAdviser(user)"
                                    class="cursor-pointer px-3 py-2 hover:bg-gray-100"
                                >
                                    <div class="break-words text-sm font-medium">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </div>
                                    <div class="break-all text-[11px] text-gray-500">
                                        {{ user.email }}
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="isSearchingAdviser"
                                class="mt-1 text-[11px] text-gray-500"
                            >
                                Searching adviser...
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="pt-2 md:col-span-2">
                        <button
                            type="submit"
                            :disabled="isSaving"
                            class="flex w-full items-center justify-center gap-2 rounded-full bg-[#0C4B05] px-5 py-2.5 text-sm text-white transition-all duration-200 hover:bg-[#0a3d04] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50 md:w-auto"
                        >
                            <LoaderCircle
                                v-if="isSaving"
                                class="h-4 w-4 animate-spin"
                            />
                            {{ isSaving ? 'Creating...' : 'Create Project' }}
                        </button>
                    </div>
                </form>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>