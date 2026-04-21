<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import AppButton from '@/components/AppButton.vue'
import PageSectionHeader from '@/components/PageSectionHeader.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { useDisplayFormatters } from '@/composables/useDisplayFormatter'
import { useAlerts } from '@/composables/useAlerts'
import type { User, Role, PaginatedUsers } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { FolderOpen } from 'lucide-vue-next'
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty'

const props = defineProps<{
    users: PaginatedUsers
    focalRoleId: number
    hasExistingFocal: boolean
}>()

const loadingId = ref<number | null>(null)

const { fullName, initials } = useDisplayFormatters()

const {
    showSuccessAlert,
    showErrorAlert,
    confirmAction,
} = useAlerts()

const isFocal = (user: User & { roles?: Role[] }) =>
    user.roles?.some((role) => role.id === props.focalRoleId) ?? false

const getUserFullName = (user: User) => fullName(user, 'No name')
const getUserInitials = (user: User) => initials(user, 'NA')

const toggleFocalRole = async (userId: number, user: User & { roles?: Role[] }) => {
    if (loadingId.value !== null) return

    const removing = isFocal(user)

    const confirm = await confirmAction(
        removing ? 'Remove Role?' : 'Assign Role?',
        removing
            ? 'Are you sure you want to remove this user as Focal Person?'
            : 'Assign this user as Focal Person?',
        removing ? 'Remove' : 'Assign',
    )

    if (!confirm.isConfirmed) return

    loadingId.value = userId

    router.post(
        route('departmentchair.toggle-focal', { user: userId }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showSuccessAlert(
                    'Success',
                    removing
                        ? 'Focal role removed successfully.'
                        : 'Focal role assigned successfully.',
                )

                router.reload({ only: ['users', 'hasExistingFocal'] })
            },
            onError: () => {
                showErrorAlert(
                    'Error',
                    removing
                        ? 'Failed to remove role.'
                        : 'Failed to assign role.',
                )
            },
            onFinish: () => {
                loadingId.value = null
            },
        },
    )
}

const paginationLinks = computed(() => props.users.links)

const goToPage = (url: string | null) => {
    if (!url) return
    router.get(url, {}, { preserveScroll: true, preserveState: true })
}
</script>

<template>
    <Head title="Assign Faculty" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>Assign Faculty</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <PageSectionHeader
                    title="Faculty Members"
                    description="Assign or remove the focal faculty role."
                />

                <!-- EMPTY STATE -->
                <div
                    v-if="!props.users.data.length"
                    class="flex min-h-[60vh] items-center justify-center"
                >
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FolderOpen />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Faculty Found</EmptyTitle>

                        <EmptyDescription>
                            There are currently no faculty members available.
                        </EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>

                <!-- LIST -->
                <div
                    v-else
                    class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="user in props.users.data"
                        :key="user.id"
                        class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-200 text-sm font-semibold text-gray-700">
                                {{ getUserInitials(user) }}
                            </div>

                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-semibold text-gray-900">
                                    {{ getUserFullName(user) }}
                                </h2>

                                <p class="truncate text-xs text-gray-500">
                                    {{ user.email ?? 'No email provided' }}
                                </p>
                            </div>
                        </div>

                        <div class="mb-3 border-t border-gray-100 pt-3">
                            <p class="text-xs text-gray-500">Expertise</p>
                            <p class="text-sm font-medium text-gray-900">
                                {{ user.expertise ?? 'No expertise provided' }}
                            </p>
                        </div>

                        <div class="mb-4 border-t border-gray-100 pt-3">
                            <p class="mb-1 text-xs text-gray-500">Roles</p>

                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="role in user.roles || []"
                                    :key="role.id"
                                    class="rounded-full border border-gray-200 px-2 py-0.5 text-xs text-gray-700"
                                >
                                    {{ role.name }}
                                </span>

                                <span
                                    v-if="!user.roles || user.roles.length === 0"
                                    class="text-xs text-gray-400"
                                >
                                    No roles assigned
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="isFocal(user) || !props.hasExistingFocal"
                            class="mt-auto pt-4"
                        >
                            <AppButton
                                :variant="isFocal(user) ? 'danger' : 'primary'"
                                :disabled="loadingId === user.id"
                                block
                                @click="toggleFocalRole(user.id, user)"
                            >
                                {{ isFocal(user) ? 'Remove' : 'Assign' }}
                            </AppButton>
                        </div>
                    </div>
                </div>

                <!-- PAGINATION -->
                <div
                    v-if="props.users.data.length && paginationLinks.length > 3"
                    class="flex flex-wrap justify-center gap-2"
                >
                    <button
                        v-for="(link, i) in paginationLinks"
                        :key="i"
                        @click="goToPage(link.url)"
                        :disabled="!link.url"
                        class="rounded-lg border px-4 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                        :class="link.active ? 'bg-blue-600 text-white' : 'bg-white'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>