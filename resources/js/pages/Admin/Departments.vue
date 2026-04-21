<script setup lang="ts">
import AppSidebar from '@/components/AdminAppSider.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { computed, ref, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

interface DepartmentItem {
    id: number
    name: string
    college_name: string
    total_users: number
    created_at: string | null
}

interface CollegeItem {
    id: number
    name: string
}

interface FlashProps {
    success?: string | null
}

const props = defineProps<{
    departments: DepartmentItem[]
    colleges: CollegeItem[]
    defaultCollegeId?: number | null
    flash?: FlashProps
}>()

const showModal = ref(false)
const search = ref('')

const form = useForm({
    name: '',
    college_id: props.defaultCollegeId ?? '',
})

watch(showModal, (value) => {
    if (value) {
        form.reset()
        form.college_id = props.defaultCollegeId ?? ''
    }
})

const filteredDepartments = computed(() => {
    if (!search.value) return props.departments

    return props.departments.filter((department) =>
        `${department.name} ${department.college_name}`
            .toLowerCase()
            .includes(search.value.toLowerCase())
    )
})

const submit = () => {
    form.post(route('admin.departments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            form.reset()
            form.college_id = props.defaultCollegeId ?? ''
        },
    })
}
</script>

<template>
    <Head title="Departments" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem class="font-semibold">Departments</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <!-- Success Message -->
                <div
                    v-if="props.flash?.success"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                >
                    {{ props.flash.success }}
                </div>

                <!-- Header / Actions -->
                <div class="rounded-xl border bg-muted/40 p-4">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-base font-semibold">Departments</h2>
                            <p class="text-sm text-muted-foreground">
                                Manage departments and view their total users.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search department..."
                                class="w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 sm:w-72"
                            />

                            <button
                                type="button"
                                @click="showModal = true"
                                class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90"
                            >
                                Add Department
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-xl border bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left">
                            <tr>
                                <th class="p-3 font-semibold">Department</th>
                                <th class="p-3 font-semibold">College</th>
                                <th class="p-3 font-semibold">Total Users</th>
                                <th class="p-3 font-semibold">Created</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="department in filteredDepartments"
                                :key="department.id"
                                class="border-t transition hover:bg-muted/30"
                            >
                                <td class="p-3 font-medium">{{ department.name }}</td>
                                <td class="p-3">{{ department.college_name }}</td>
                                <td class="p-3">
                                    <span
                                        class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700"
                                    >
                                        {{ department.total_users }}
                                    </span>
                                </td>
                                <td class="p-3">{{ department.created_at ?? '—' }}</td>
                            </tr>

                            <tr v-if="filteredDepartments.length === 0">
                                <td colspan="4" class="p-6 text-center text-gray-500">
                                    No departments found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal -->
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
            >
                <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b p-4">
                        <h2 class="text-lg font-bold">Add Department</h2>
                        <button
                            type="button"
                            @click="showModal = false"
                            class="text-lg text-gray-500 hover:text-gray-700"
                        >
                            ✕
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4 p-6">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Department Name
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-lg border px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                placeholder="Enter department name"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                College
                            </label>
                            <select
                                v-model="form.college_id"
                                class="w-full rounded-lg border px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                            >
                                <option disabled value="">Select college</option>
                                <option
                                    v-for="college in props.colleges"
                                    :key="college.id"
                                    :value="college.id"
                                >
                                    {{ college.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.college_id" class="mt-1 text-sm text-red-500">
                                {{ form.errors.college_id }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                {{ form.processing ? 'Saving...' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>