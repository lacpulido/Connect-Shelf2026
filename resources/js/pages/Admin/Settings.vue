<script setup lang="ts">
import AppSidebar from '@/components/AdminAppSider.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { useForm, usePage } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
type Admin = {
    id: number
    first_name: string
    middle_name: string | null
    last_name: string
    extension_name: string | null
    email: string
}

type PageProps = {
    admin: Admin
    flash?: {
        success?: string
    }
}

const page = usePage<PageProps>()

const profileForm = useForm({
    first_name: page.props.admin.first_name ?? '',
    middle_name: page.props.admin.middle_name ?? '',
    last_name: page.props.admin.last_name ?? '',
    extension_name: page.props.admin.extension_name ?? '',
    email: page.props.admin.email ?? '',
})

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const submitProfile = () => {
    profileForm.patch(route('admin.settings.update-profile'), {
        preserveScroll: true,
    })
}

const submitPassword = () => {
    passwordForm.patch(route('admin.settings.update-password'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset()
        },
    })
}
</script>

<template>
    <Head title="Settings" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem class="font-semibold">Settings</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-6 p-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Manage your profile information and password.
                    </p>
                </div>

                <div
                    v-if="page.props.flash?.success"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <section class="rounded-xl border bg-white p-6 shadow-sm">
                        <div class="mb-5">
                            <h2 class="text-lg font-semibold text-gray-900">Update Profile</h2>
                            <p class="mt-1 text-sm text-gray-500">
                                Update your account details.
                            </p>
                        </div>

                        <form @submit.prevent="submitProfile" class="space-y-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    First Name
                                </label>
                                <input
                                    v-model="profileForm.first_name"
                                    type="text"
                                    class="w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                />
                                <p v-if="profileForm.errors.first_name" class="mt-1 text-sm text-red-600">
                                    {{ profileForm.errors.first_name }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Middle Name
                                </label>
                                <input
                                    v-model="profileForm.middle_name"
                                    type="text"
                                    class="w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                />
                                <p v-if="profileForm.errors.middle_name" class="mt-1 text-sm text-red-600">
                                    {{ profileForm.errors.middle_name }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Last Name
                                </label>
                                <input
                                    v-model="profileForm.last_name"
                                    type="text"
                                    class="w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                />
                                <p v-if="profileForm.errors.last_name" class="mt-1 text-sm text-red-600">
                                    {{ profileForm.errors.last_name }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Extension Name
                                </label>
                                <input
                                    v-model="profileForm.extension_name"
                                    type="text"
                                    placeholder="Optional"
                                    class="w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                />
                                <p v-if="profileForm.errors.extension_name" class="mt-1 text-sm text-red-600">
                                    {{ profileForm.errors.extension_name }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Email
                                </label>
                                <input
                                    v-model="profileForm.email"
                                    type="email"
                                    class="w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                />
                                <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">
                                    {{ profileForm.errors.email }}
                                </p>
                            </div>

                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="profileForm.processing"
                                    class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {{ profileForm.processing ? 'Saving...' : 'Save Profile' }}
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="rounded-xl border bg-white p-6 shadow-sm">
                        <div class="mb-5">
                            <h2 class="text-lg font-semibold text-gray-900">Update Password</h2>
                            <p class="mt-1 text-sm text-gray-500">
                                Change your account password securely.
                            </p>
                        </div>

                        <form @submit.prevent="submitPassword" class="space-y-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Current Password
                                </label>
                                <input
                                    v-model="passwordForm.current_password"
                                    type="password"
                                    class="w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                />
                                <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">
                                    {{ passwordForm.errors.current_password }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    New Password
                                </label>
                                <input
                                    v-model="passwordForm.password"
                                    type="password"
                                    class="w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                />
                                <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">
                                    {{ passwordForm.errors.password }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Confirm New Password
                                </label>
                                <input
                                    v-model="passwordForm.password_confirmation"
                                    type="password"
                                    class="w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                />
                            </div>

                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>