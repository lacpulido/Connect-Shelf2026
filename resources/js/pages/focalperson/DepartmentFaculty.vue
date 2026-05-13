<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Eye, EyeOff, FolderKanban, Mail, Users } from 'lucide-vue-next'

type Faculty = {
    id: number
    email: string
    first_name: string
    middle_name?: string | null
    last_name: string
    extension_name?: string | null
    expertise?: string | null
    department_id: number
    user_type: number
    status?: string | number | null
    adviser_is_visible: boolean
    projects_count: number
}

type PaginatedFaculty = {
    data: Faculty[]
    total: number
    current_page: number
    last_page: number
    per_page: number
}

const props = defineProps<{
    faculty: PaginatedFaculty
}>()

const getFacultyName = (faculty: Faculty) => {
    return [
        faculty.first_name,
        faculty.middle_name,
        faculty.last_name,
        faculty.extension_name,
    ]
        .filter(Boolean)
        .join(' ')
}

const showFaculty = (facultyId: number) => {
    router.patch(
        route('focalperson.department.faculty.show', facultyId),
        {},
        {
            preserveScroll: true,
        },
    )
}

const hideFaculty = (facultyId: number) => {
    router.patch(
        route('focalperson.department.faculty.hide', facultyId),
        {},
        {
            preserveScroll: true,
        },
    )
}
</script>

<template>
    <Head title="Department Faculty" />

    <AppLayout>
        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-gray-300 bg-white px-5 py-6 shadow-sm sm:px-7">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-gray-200 bg-gray-100">
                            <Users class="h-6 w-6 text-[#0C4B05]" />
                        </div>

                        <div>
                            <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">
                                Department Faculty
                            </h1>

                            <p class="mt-1 max-w-2xl text-sm text-gray-600">
                                Manage the faculty members from your assigned department.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <div v-if="props.faculty.data.length">
                    <div class="hidden lg:block">
                        <div class="grid grid-cols-12 border-b border-gray-200 px-5 py-4 text-xs font-bold uppercase tracking-wide text-gray-500">
                            <div class="col-span-3">Faculty</div>
                            <div class="col-span-3">Email</div>
                            <div class="col-span-2">Expertise</div>
                            <div class="col-span-1 text-center">Projects</div>
                            <div class="col-span-1 text-center">Status</div>
                            <div class="col-span-2 text-center">Actions</div>
                        </div>

                        <div
                            v-for="facultyMember in props.faculty.data"
                            :key="facultyMember.id"
                            class="grid grid-cols-12 items-center border-b border-gray-100 px-5 py-5 transition hover:bg-gray-50 last:border-b-0"
                        >
                            <div class="col-span-3 flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#0C4B05]/10 text-sm font-bold text-[#0C4B05]">
                                    {{ facultyMember.first_name?.charAt(0) }}{{ facultyMember.last_name?.charAt(0) }}
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-gray-900">
                                        {{ getFacultyName(facultyMember) }}
                                    </p>

                                    <p class="text-xs font-medium text-gray-500">
                                        Faculty Member
                                    </p>
                                </div>
                            </div>

                            <div class="col-span-3">
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <Mail class="h-4 w-4 shrink-0 text-gray-400" />
                                    <span class="truncate">
                                        {{ facultyMember.email }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-span-2">
                                <p class="truncate text-sm font-semibold text-gray-700">
                                    {{ facultyMember.expertise || 'No expertise listed' }}
                                </p>
                            </div>

                            <div class="col-span-1">
                                <div class="flex items-center justify-center gap-2 text-sm font-bold text-gray-800">
                                    <FolderKanban class="h-4 w-4 text-[#0C4B05]" />
                                    {{ facultyMember.projects_count ?? 0 }}
                                </div>
                            </div>

                            <div class="col-span-1 text-center">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                        facultyMember.adviser_is_visible
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-red-100 text-red-700',
                                    ]"
                                >
                                    {{ facultyMember.adviser_is_visible ? 'Visible' : 'Hidden' }}
                                </span>
                            </div>

                            <div class="col-span-2">
                                <div class="flex items-center justify-center">
                                    <button
                                        v-if="!facultyMember.adviser_is_visible"
                                        type="button"
                                        @click="showFaculty(facultyMember.id)"
                                        class="inline-flex items-center gap-1 text-sm font-semibold text-[#0C4B05] transition hover:opacity-70"
                                    >
                                        <Eye class="h-4 w-4" />
                                        Show
                                    </button>

                                    <button
                                        v-else
                                        type="button"
                                        @click="hideFaculty(facultyMember.id)"
                                        class="inline-flex items-center gap-1 text-sm font-semibold text-red-500 transition hover:opacity-70"
                                    >
                                        <EyeOff class="h-4 w-4" />
                                        Hide
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 lg:hidden">
                        <div
                            v-for="facultyMember in props.faculty.data"
                            :key="facultyMember.id"
                            class="py-5"
                        >
                            <div class="flex items-start gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#0C4B05]/10 text-sm font-bold text-[#0C4B05]">
                                    {{ facultyMember.first_name?.charAt(0) }}{{ facultyMember.last_name?.charAt(0) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="break-words font-bold text-gray-900">
                                            {{ getFacultyName(facultyMember) }}
                                        </p>

                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                                facultyMember.adviser_is_visible
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-red-100 text-red-700',
                                            ]"
                                        >
                                            {{ facultyMember.adviser_is_visible ? 'Visible' : 'Hidden' }}
                                        </span>
                                    </div>

                                    <div class="mt-1 flex items-start gap-2 text-sm text-gray-600">
                                        <Mail class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                                        <span class="break-all">
                                            {{ facultyMember.email }}
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm font-semibold text-gray-700">
                                        Expertise:
                                        <span class="font-medium text-gray-600">
                                            {{ facultyMember.expertise || 'No expertise listed' }}
                                        </span>
                                    </p>

                                    <div class="mt-2 flex items-center gap-2 text-sm font-semibold text-gray-700">
                                        <FolderKanban class="h-4 w-4 text-[#0C4B05]" />
                                        Projects:
                                        <span class="font-bold text-gray-900">
                                            {{ facultyMember.projects_count ?? 0 }}
                                        </span>
                                    </div>

                                    <div class="mt-4 flex items-center gap-4">
                                        <button
                                            v-if="!facultyMember.adviser_is_visible"
                                            type="button"
                                            @click="showFaculty(facultyMember.id)"
                                            class="inline-flex items-center gap-1 text-sm font-semibold text-[#0C4B05] transition hover:opacity-70"
                                        >
                                            <Eye class="h-4 w-4" />
                                            Show
                                        </button>

                                        <button
                                            v-else
                                            type="button"
                                            @click="hideFaculty(facultyMember.id)"
                                            class="inline-flex items-center gap-1 text-sm font-semibold text-red-500 transition hover:opacity-70"
                                        >
                                            <EyeOff class="h-4 w-4" />
                                            Hide
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="py-16 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                        <Users class="h-7 w-7 text-gray-400" />
                    </div>

                    <h2 class="mt-4 text-base font-bold text-gray-900">
                        No faculty found
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        There are no faculty members assigned to your department yet.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>