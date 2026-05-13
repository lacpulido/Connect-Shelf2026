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

type Faculty = {
    id: number
    name: string
    email: string
    department_id?: number | null
    department_name?: string | null
    adviser_is_visible?: boolean
}

const props = defineProps<{
    colleges: Array<{ id: number; name: string }>
    departments: Array<{ id: number; name: string; college_id: number }>
    faculty: Faculty[]
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
const selectedAdviserId = ref<number | ''>('')

let timeout: ReturnType<typeof setTimeout> | null = null

const getCurrentAcademicYear = () => {
    const now = new Date()
    const year = now.getFullYear()
    const month = now.getMonth() + 1

    return month >= 8 ? `${year}-${year + 1}` : `${year - 1}-${year}`
}

const getProjectTypeByDepartment = (departmentId: number | string | null) => {
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
        .trim()
        .slice(0, 200)
}

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

const form = useForm({
    proposal_titles: ['', '', ''],
    proposal_files: [null, null, null] as Array<File | null>,

    researchers: [] as Array<{
        id: number
        name: string
        email: string
    }>,

    preferred_advisers: [] as Array<{
        id: number
        name: string
        email: string
    }>,

    preferred_adviser_ids: [] as number[],
})

const availableFaculty = computed(() => {
    return props.faculty.filter((faculty) => {
        const alreadySelected = form.preferred_advisers.find(
            (adviser) => adviser.id === faculty.id,
        )

        const isVisible =
            faculty.adviser_is_visible === undefined ||
            faculty.adviser_is_visible === true

        return !alreadySelected && isVisible
    })
})

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

const addAdviserFromDropdown = () => {
    if (!selectedAdviserId.value) return
    if (form.preferred_advisers.length >= 3) return

    const adviser = availableFaculty.value.find(
        (faculty) => faculty.id === Number(selectedAdviserId.value),
    )

    if (!adviser) return

    form.preferred_advisers.push({
        id: adviser.id,
        name: adviser.name,
        email: adviser.email,
    })

    form.preferred_adviser_ids = form.preferred_advisers.map((a) => a.id)

    selectedAdviserId.value = ''
}

const removeAdviser = (id: number) => {
    form.preferred_advisers = form.preferred_advisers.filter((a) => a.id !== id)
    form.preferred_adviser_ids = form.preferred_advisers.map((a) => a.id)
}

const handleProposalFile = (event: Event, index: number) => {
    const input = event.target as HTMLInputElement
    form.proposal_files[index] = input.files?.[0] ?? null
}

const resetFormState = () => {
    form.reset()
    form.clearErrors()

    form.proposal_titles = ['', '', '']
    form.proposal_files = [null, null, null]
    form.researchers = []
    form.preferred_advisers = []
    form.preferred_adviser_ids = []

    searchQuery.value = ''
    searchResults.value = []
    selectedAdviserId.value = ''
}

const submit = () => {
    isSaving.value = true
    form.clearErrors()

    form.transform((data) => ({
        proposal_titles: data.proposal_titles.map((title) =>
            sanitizePlainText(title),
        ),
        proposal_files: data.proposal_files,
        researchers: data.researchers.map((r) => r.id),
        preferred_adviser_ids: data.preferred_advisers.map((a) => a.id),
    })).post(route('student.projects.store'), {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: async () => {
            resetFormState()

            await showSuccessAlert(
                'Topic Proposal Submitted! 🎉',
                'Your topic proposal has been submitted successfully.',
            )

            window.location.href = route('student.dashboard')
        },

        onError: async (errors) => {
            const errorMessage =
                errors.error ||
                errors.proposal_files ||
                errors['proposal_files.0'] ||
                errors['proposal_files.1'] ||
                errors['proposal_files.2'] ||
                errors.preferred_adviser_ids ||
                errors['preferred_adviser_ids.0'] ||
                errors['preferred_adviser_ids.1'] ||
                errors['preferred_adviser_ids.2'] ||
                errors.researchers ||
                errors['researchers.0'] ||
                errors['proposal_titles.0'] ||
                errors['proposal_titles.1'] ||
                errors['proposal_titles.2'] ||
                errors.proposal_titles ||
                'Please check all required fields.'

            await showErrorAlert('Error', errorMessage)

            isSaving.value = false
        },

        onFinish: () => {
            isSaving.value = false
        },
    })
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
                    <div
                        v-if="form.errors.error"
                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 md:col-span-2"
                    >
                        {{ form.errors.error }}
                    </div>

                    <!-- Project Details -->
                    <div class="md:col-span-2">
                        <div class="mb-3">
                            <h2 class="text-based font-semibold text-black-900">
                                Project Details
                            </h2>

                           
                        </div>
                    </div>

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

                    <!-- Proposal Topics -->
                    <div class="md:col-span-2">
                        <div class="mb-3 mt-2">
                            <h2 class="text-sm font-semibold text-gray-900">
                                Topic Proposal Titles
                                <span class="text-red-600">*</span>
                            </h2>

                            <p class="mt-1 text-xs text-gray-500">
                                Enter 3 proposed project titles and upload one file for each title.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div
                                v-for="(_, index) in form.proposal_titles"
                                :key="index"
                                class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                            >
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Proposal Title {{ index + 1 }}
                                    <span class="text-red-600">*</span>
                                </label>

                                <input
                                    v-model="form.proposal_titles[index]"
                                    type="text"
                                    maxlength="200"
                                    :placeholder="`Enter proposal title ${index + 1}`"
                                    class="w-full rounded-md border px-3 py-2 text-sm"
                                />

                                <div class="mt-1 flex items-center justify-between">
                                    <div
                                        v-if="(form.errors as Record<string, string>)[`proposal_titles.${index}`]"
                                        class="text-xs text-red-600"
                                    >
                                        {{
                                            (form.errors as Record<string, string>)[
                                                `proposal_titles.${index}`
                                            ]
                                        }}
                                    </div>

                                    <div v-else></div>

                                    <div class="text-xs text-gray-500">
                                        {{ form.proposal_titles[index].length }}/200
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        Proposal File {{ index + 1 }}
                                        <span class="text-red-600">*</span>
                                    </label>

                                    <input
                                        type="file"
                                        accept=".pdf,.doc,.docx"
                                        @change="handleProposalFile($event, index)"
                                        class="block w-full rounded-md border px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#0C4B05] file:px-3 file:py-1.5 file:text-sm file:text-white"
                                    />

                                    <p class="mt-1 text-xs text-gray-500">
                                        Upload PDF, DOC, or DOCX for Proposal Title {{ index + 1 }}.
                                    </p>

                                    <div
                                        v-if="(form.errors as Record<string, string>)[`proposal_files.${index}`]"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{
                                            (form.errors as Record<string, string>)[
                                                `proposal_files.${index}`
                                            ]
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Researchers -->
                    <div class="min-w-0 md:col-span-2">
                        <div class="flex h-full flex-col">
                            <div class="mb-3">
                                <div class="flex items-start justify-between gap-3">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Researchers
                                        <span class="text-red-600">*</span>
                                    </label>

                                    <span class="text-xs text-gray-500">
                                        {{ form.researchers.length + 1 }}/5
                                    </span>
                                </div>

                                <p class="mt-1 text-xs text-gray-500">
                                    Maximum of 5 members including the leader
                                </p>
                            </div>

                            <div class="mb-2 flex min-h-[80px] items-start rounded-md border border-dashed border-gray-200 bg-gray-50 px-3 py-3">
                                <div
                                    v-if="form.researchers.length"
                                    class="flex flex-wrap gap-1.5"
                                >
                                    <div
                                        v-for="r in form.researchers"
                                        :key="r.id"
                                        class="flex max-w-full items-center gap-1 rounded-full bg-[#0C4B05] px-2.5 py-1 text-[11px] text-white"
                                    >
                                        <span class="max-w-[180px] truncate md:max-w-[260px]">
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

                                <div v-else class="text-xs text-gray-400">
                                    No researcher selected yet.
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
                                    {{
                                        (form.errors as Record<string, string>)[
                                            'researchers.0'
                                        ]
                                    }}
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

<!-- Preferred Advisers -->
<!-- Preferred Advisers -->
<div class="min-w-0 md:col-span-2">
    <div class="flex h-full flex-col">
        <div class="mb-3">
            <div class="flex items-start justify-between gap-3">
                <label class="block text-sm font-medium text-gray-700">
                    Preferred Advisers
                    <span class="text-red-600">*</span>
                </label>

                <span class="text-sm text-gray-500">
                    {{ form.preferred_advisers.length }}/3
                </span>
            </div>

            <p class="mt-1 text-sm text-gray-500">
                Select exactly 3 preferred advisers. First selected adviser is the priority.
            </p>
        </div>

        <div
            class="mb-2 flex min-h-[80px] items-start rounded-md border border-dashed border-gray-200 bg-white px-3 py-3"
        >
            <div
                v-if="form.preferred_advisers.length"
                class="flex flex-wrap gap-2"
            >
                <div
                    v-for="(adviser, index) in form.preferred_advisers"
                    :key="adviser.id"
                    class="flex max-w-full items-center gap-2 rounded-full bg-gray-200 px-4 py-2 text-sm font-medium text-black-700"
                >
                    <span class="max-w-[180px] truncate md:max-w-[260px]">
                        {{ adviser.name }}
                    </span>

                    <!-- Priority Plain Text -->
                   <span
    v-if="index === 0"
    class="ml-1 text-xs font-semibold text-gray-600"
>
    (Priority)
</span>

                    <button
                        type="button"
                        @click="removeAdviser(adviser.id)"
                        class="ml-1 text-sm text-gray-500 transition hover:text-red-600"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <div v-else class="text-sm text-gray-400">
                No adviser selected yet.
            </div>
        </div>

        <div class="flex flex-col gap-2 md:flex-row md:items-center">
            <select
                v-model="selectedAdviserId"
                :disabled="form.preferred_advisers.length >= 3"
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-[#0C4B05] focus:outline-none focus:ring-1 focus:ring-[#0C4B05] disabled:cursor-not-allowed disabled:bg-gray-100"
            >
                <option value="">
                    Choose faculty adviser
                </option>

                <option
                    v-for="faculty in availableFaculty"
                    :key="faculty.id"
                    :value="faculty.id"
                >
                    {{ faculty.name }} -
                    {{ faculty.department_name ?? 'No Department' }} -
                    {{ faculty.email }}
                </option>
            </select>

            <button
                type="button"
                @click="addAdviserFromDropdown"
                :disabled="!selectedAdviserId || form.preferred_advisers.length >= 3"
                class="rounded-md bg-[#0C4B05] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0a3d04] disabled:cursor-not-allowed disabled:opacity-50 md:shrink-0"
            >
                Add 
            </button>
        </div>

        <div class="mt-2 min-h-[20px]">
            <div
                v-if="form.preferred_advisers.length >= 3"
                class="text-sm text-amber-600"
            >
                You already selected 3 preferred advisers.
            </div>

            <div
                v-else-if="availableFaculty.length === 0"
                class="text-sm text-gray-500"
            >
                No visible faculty advisers available.
            </div>

            <div
                v-else-if="form.errors.preferred_adviser_ids"
                class="text-sm text-red-600"
            >
                {{ form.errors.preferred_adviser_ids }}
            </div>

            <div
                v-else-if="(form.errors as Record<string, string>)['preferred_adviser_ids.0']"
                class="text-sm text-red-600"
            >
                {{
                    (form.errors as Record<string, string>)[
                        'preferred_adviser_ids.0'
                    ]
                }}
            </div>
        </div>
    </div>
</div>
                    <div class="pb-20 pt-4 md:col-span-2 md:pb-10">
                        <button
                            type="submit"
                            :disabled="isSaving"
                            class="flex w-full items-center justify-center gap-2 rounded-full bg-[#0C4B05] px-5 py-2.5 text-sm text-white transition-all duration-200 hover:bg-[#0a3d04] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50 md:w-auto"
                        >
                            <LoaderCircle
                                v-if="isSaving"
                                class="h-4 w-4 animate-spin"
                            />

                            {{
                                isSaving
                                    ? 'Submitting...'
                                    : 'Submit Topic Proposal'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>