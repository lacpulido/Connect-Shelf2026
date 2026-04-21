<script setup lang="ts">
import AppSidebar from '@/components/AdminAppSider.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useAlerts } from '@/composables/useAlerts'
import Swal from 'sweetalert2'
import type { User } from '../../types/admin-users'

const {
    showSuccessAlert,
    showErrorAlert,
    showInfoAlert,
    showWarningAlert,
} = useAlerts()

const confirmAction = (
    title: string,
    text: string,
    confirmButtonText = 'Confirm',
) => {
    return Swal.fire({
        icon: 'warning',
        title,
        text,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0C4B05',
        cancelButtonColor: '#dc2626',
        width: 320,
        padding: '1rem',
        customClass: {
            popup: 'rounded-xl',
            title: 'text-lg font-semibold',
            htmlContainer: 'text-sm',
            confirmButton: 'px-4 py-1.5 text-sm rounded-md',
            cancelButton: 'px-4 py-1.5 text-sm rounded-md',
        },
    })
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type PaginatedUsers = {
    data: User[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: PaginationLink[]
}

type AssignRoleProps = {
    users: PaginatedUsers
    departments: string[]
    filters?: {
        department?: string
    }
    chairRoleId: number
    departmentsWithChair: number[]
    feedbackMessage?: string
}

const props = defineProps<AssignRoleProps>()

const department = ref(props.filters?.department ?? '')
const selectedDepartment = ref('')
const showDepartmentSelect = ref<number | null>(null)

const feedbackMessage = computed(() => props.feedbackMessage ?? '')
const paginatedUsers = computed(() => props.users.data ?? [])

const applyFilters = () => {
    router.get(
        route('admin.users.assign-role'),
        {
            department: department.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    )
}

const isChair = (user: User) => {
    return user.roles?.some((role: any) => role.id === props.chairRoleId)
}

const departmentHasChair = (departmentId: number | null | undefined) => {
    if (!departmentId) return false
    return props.departmentsWithChair?.includes(departmentId)
}

const isDepartmentTakenByName = (departmentName: string) => {
    return paginatedUsers.value.some(
        (u) => u.department?.name === departmentName && isChair(u),
    )
}

const canAssignChair = (user: User) => {
    if (isChair(user)) return false
    if (!user.department?.id) return true
    return !departmentHasChair(user.department.id)
}

const toggleChair = async (user: User) => {
    if (isChair(user)) {
        const result = await confirmAction(
            'Remove Chair Role?',
            `Remove Department Chair role from ${user.first_name} ${user.last_name}?`,
            'Yes, remove',
        )

        if (!result.isConfirmed) return

        router.post(
            route('admin.users.toggle-chair', { user: user.id }),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    showSuccessAlert('Removed', 'Chair role removed successfully.')
                },
                onError: (errors) => {
                    showErrorAlert('Failed', errors.error ?? 'Failed to remove chair role.')
                },
            },
        )

        return
    }

    if (!canAssignChair(user)) {
        showInfoAlert('Unavailable', 'This department already has a Department Chair.')
        return
    }

    showDepartmentSelect.value = showDepartmentSelect.value === user.id ? null : user.id
    selectedDepartment.value = user.department?.name ?? ''
}

const confirmAssignChair = async (userId: number) => {
    if (!selectedDepartment.value) {
        showWarningAlert('Department Required', 'Please select a department first.')
        return
    }

    const currentUser = paginatedUsers.value.find((u) => u.id === userId)

    if (
        isDepartmentTakenByName(selectedDepartment.value) &&
        selectedDepartment.value !== currentUser?.department?.name
    ) {
        showErrorAlert('Department Already Assigned', 'This department already has a Department Chair.')
        return
    }

    const result = await confirmAction(
        'Assign Chair Role?',
        `Assign this faculty as Department Chair of ${selectedDepartment.value}?`,
        'Yes, assign',
    )

    if (!result.isConfirmed) return

    router.post(
        route('admin.users.toggle-chair', { user: userId }),
        {
            department: selectedDepartment.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showDepartmentSelect.value = null
                selectedDepartment.value = ''
                showSuccessAlert('Assigned', 'Chair role assigned successfully.')
            },
            onError: (errors) => {
                showErrorAlert('Failed', errors.error ?? 'Failed to assign chair role.')
            },
        },
    )
}
</script>

<template>
    <Head title="AssignRole" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>Assign Role</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <div
                    v-if="feedbackMessage"
                    class="rounded-lg bg-green-100 px-4 py-2 text-sm text-green-800"
                >
                    {{ feedbackMessage }}
                </div>

                <div class="rounded-xl border bg-muted/40 p-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="text-sm font-medium text-gray-600">
                            Filter by Department:
                        </div>

                        <select
                            v-model="department"
                            @change="applyFilters"
                            class="rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        >
                            <option value="">All Departments</option>
                            <option
                                v-for="dept in props.departments"
                                :key="dept"
                                :value="dept"
                            >
                                {{ dept }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left">
                            <tr>
                                <th class="p-3">First Name</th>
                                <th class="p-3">Last Name</th>
                                <th class="p-3">Department</th>
                                <th class="p-3">Email</th>
                                <th class="p-3">Current Role</th>
                                <th class="p-3">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="user in paginatedUsers"
                                :key="user.id"
                                class="border-t align-top transition hover:bg-muted/30"
                            >
                                <td class="p-3">{{ user.first_name }}</td>
                                <td class="p-3">{{ user.last_name }}</td>
                                <td class="p-3">{{ user.department?.name ?? '—' }}</td>
                                <td class="p-3">{{ user.email }}</td>
                                <td class="p-3">
                                    <span class="text-gray-700">
                                        {{ isChair(user) ? 'Department Chair' : 'Faculty' }}
                                    </span>
                                </td>

                                <td class="p-3">
                                    <div class="flex flex-col gap-2">
                                        <button
                                            v-if="isChair(user)"
                                            @click="toggleChair(user)"
                                            class="rounded-md bg-red-500 px-3 py-2 text-xs font-medium text-white transition hover:bg-red-600"
                                        >
                                            Remove Chair
                                        </button>

                                        <button
                                            v-else-if="canAssignChair(user)"
                                            @click="toggleChair(user)"
                                            class="rounded-md bg-[#0C4B05] px-3 py-2 text-xs font-medium text-white transition hover:bg-[#093804]"
                                        >
                                            Assign Chair
                                        </button>

                                        <span
                                            v-else
                                            class="inline-flex rounded-md bg-gray-100 px-3 py-2 text-xs font-medium text-gray-500"
                                        >
                                            Chair already assigned
                                        </span>

                                        <div
                                            v-if="showDepartmentSelect === user.id"
                                            class="mt-1 flex items-center gap-2"
                                        >
                                            <select
                                                v-model="selectedDepartment"
                                                class="rounded border px-3 py-2 text-xs"
                                            >
                                                <option disabled value="">Select Department</option>
                                                <option
                                                    v-for="dept in props.departments"
                                                    :key="dept"
                                                    :value="dept"
                                                    :disabled="
                                                        isDepartmentTakenByName(dept) &&
                                                        dept !== user.department?.name
                                                    "
                                                >
                                                    {{ dept }}
                                                </option>
                                            </select>

                                            <button
                                                @click="confirmAssignChair(user.id)"
                                                class="rounded bg-[#0C4B05] px-3 py-2 text-xs font-medium text-white transition hover:bg-[#093804]"
                                            >
                                                Confirm
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="paginatedUsers.length === 0">
                                <td colspan="6" class="p-6 text-center text-gray-500">
                                    No faculty found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.users.last_page > 1"
                    class="flex items-center justify-between"
                >
                    <div class="text-sm text-gray-600">
                        Showing {{ props.users.from ?? 0 }} to {{ props.users.to ?? 0 }}
                        of {{ props.users.total }} faculty
                    </div>

                    <div class="flex items-center gap-2">
                        <template v-for="(link, index) in props.users.links" :key="index">
                            <span
                                v-if="!link.url"
                                class="rounded-md border px-3 py-1.5 text-sm text-gray-400"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                preserve-scroll
                                class="rounded-md border px-3 py-1.5 text-sm transition"
                                :class="
                                    link.active
                                        ? 'border-[#0C4B05] bg-[#0C4B05] text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-50'
                                "
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>