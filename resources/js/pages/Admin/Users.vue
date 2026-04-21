<script setup lang="ts">
import AppSidebar from '@/components/AdminAppSider.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

type UserItem = {
    id: number
    first_name: string
    last_name: string
    email: string
    user_type: string | null
    status: string | null
    department?: {
        name?: string
    } | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type AdminUsersProps = {
    users: {
        data: UserItem[]
        current_page: number
        last_page: number
        per_page: number
        total: number
        from: number | null
        to: number | null
        links: PaginationLink[]
    }
    departments: string[]
    filters?: {
        department?: string
        user_type?: string
    }
    feedbackMessage?: string
}

const props = defineProps<AdminUsersProps>()

const department = ref(props.filters?.department ?? '')
const userType = ref(props.filters?.user_type ?? '')
const feedbackMessage = computed(() => props.feedbackMessage ?? '')
const paginatedUsers = computed(() => props.users?.data ?? [])

const applyFilters = () => {
    router.get(
        route('admin.users'),
        {
            department: department.value || undefined,
            user_type: userType.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}
</script>

<template>
    <Head title="Users" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>Users</BreadcrumbItem>
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

                <div class="flex flex-col gap-4 rounded-xl border bg-muted/40 p-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="text-sm font-medium text-gray-600">Filters:</div>

                        <select
                            v-model="userType"
                            @change="applyFilters"
                            class="rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        >
                            <option value="">All Users</option>
                            <option value="faculty">Faculty</option>
                            <option value="student">Student</option>
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

                <div class="overflow-hidden rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left">
                            <tr>
                                <th class="p-3">First Name</th>
                                <th class="p-3">Last Name</th>
                                <th class="p-3">Department</th>
                                <th class="p-3">Email</th>
                                <th class="p-3">User Type</th>
                                <th class="p-3">Status</th>
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
                                <td class="p-3 capitalize">
                                    {{ user.user_type ?? '—' }}
                                </td>
                                <td class="p-3">
                                    <span
                                        :class="user.status === 'Active'
                                            ? 'inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700'
                                            : 'inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700'"
                                    >
                                        {{ user.status ?? '—' }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="paginatedUsers.length === 0">
                                <td colspan="6" class="p-6 text-center text-gray-500">
                                    No users found.
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
                        of {{ props.users.total }} users
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
                                    ? 'bg-[#0C4B05] text-white border-[#0C4B05]'
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