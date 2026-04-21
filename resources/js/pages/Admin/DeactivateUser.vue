<script setup lang="ts">
import AppSidebar from '@/components/AdminAppSider.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useAlerts } from '@/composables/useAlerts'

const {
    showSuccessAlert,
    showErrorAlert,
    showInfoAlert,
    showWarningAlert,
    confirmAction,
} = useAlerts()

interface Department {
    id?: number
    name: string
}

interface User {
    id: number
    first_name: string
    last_name: string
    email: string
    user_type: string | null
    is_active: boolean
    department?: Department | null
    status?: string
}

interface Filters {
    department?: string
    user_type?: string
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PaginatedUsers {
    data: User[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: PaginationLink[]
}

const props = defineProps<{
    users: PaginatedUsers
    departments: string[]
    filters?: Filters
    feedbackMessage?: string
}>()

const department = ref(props.filters?.department ?? '')
const userType = ref('faculty')
const feedbackMessage = computed(() => props.feedbackMessage ?? '')
const paginatedUsers = computed(() => props.users.data ?? [])

const applyFilters = () => {
    router.get(
        route('admin.users.deactivate.index'),
        {
            department: department.value || undefined,
            user_type: 'faculty',
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

const toggleUserStatus = async (user: User) => {
    const actionText = user.is_active ? 'deactivate' : 'reactivate'
    const actionTitle = user.is_active ? 'Deactivate Faculty?' : 'Reactivate Faculty?'
    const confirmText = user.is_active ? 'Yes, Deactivate' : 'Yes, Reactivate'

    const result = await confirmAction(
        actionTitle,
        `Are you sure you want to ${actionText} ${user.first_name} ${user.last_name}?`,
        confirmText
    )

    if (!result.isConfirmed) return

    router.patch(
        route('admin.users.toggle-active', { user: user.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showSuccessAlert(
                    user.is_active ? 'Deactivated' : 'Reactivated',
                    `User successfully ${actionText}d.`
                )
            },
            onError: (errors) => {
                showErrorAlert(
                    'Failed',
                    errors.error ?? `Failed to ${actionText} user.`
                )
            },
        }
    )
}
</script>

<template>
    <Head title="Deactivate Users" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>Deactivate User</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <div
                    v-if="feedbackMessage"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                >
                    {{ feedbackMessage }}
                </div>

                <div class="rounded-xl border bg-muted/40 p-4">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-base font-semibold">Faculty Account Status</h2>
                            <p class="text-sm text-muted-foreground">
                                Deactivate or reactivate faculty accounts.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <select
                                v-model="userType"
                                disabled
                                class="rounded-md border bg-gray-100 px-3 py-2 text-sm shadow-sm focus:outline-none"
                            >
                                <option value="faculty">Faculty</option>
                            </select>

                            <select
                                v-model="department"
                                @change="applyFilters"
                                class="rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                            >
                                <option value="">All Departments</option>
                                <option v-for="dept in props.departments" :key="dept" :value="dept">
                                    {{ dept }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left">
                            <tr>
                                <th class="p-3 font-semibold">First Name</th>
                                <th class="p-3 font-semibold">Last Name</th>
                                <th class="p-3 font-semibold">Department</th>
                                <th class="p-3 font-semibold">Email</th>
                                <th class="p-3 font-semibold">Status</th>
                                <th class="p-3 font-semibold">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="user in paginatedUsers"
                                :key="user.id"
                                class="border-t transition hover:bg-muted/30"
                            >
                                <td class="p-3">{{ user.first_name }}</td>
                                <td class="p-3">{{ user.last_name }}</td>
                                <td class="p-3">{{ user.department?.name ?? '—' }}</td>
                                <td class="p-3">{{ user.email }}</td>

                                <td class="p-3">
                                    <span
                                        :class="
                                            user.is_active
                                                ? 'inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700'
                                                : 'inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700'
                                        "
                                    >
                                        {{ user.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <td class="p-3">
                                    <button
                                        type="button"
                                        @click="toggleUserStatus(user)"
                                        :class="
                                            user.is_active
                                                ? 'inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700'
                                                : 'inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-green-700'
                                        "
                                    >
                                        {{ user.is_active ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="paginatedUsers.length === 0">
                                <td colspan="6" class="p-6 text-center text-gray-500">
                                    No faculty users found.
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
                        of {{ props.users.total }} faculty users
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
                                :class="link.active
                                    ? 'border-[#0C4B05] bg-[#0C4B05] text-white'
                                    : 'bg-white text-gray-700 hover:bg-gray-50'"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>