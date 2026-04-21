<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import SettingsLayout from '@/layouts/settings/Layout.vue';

import AppSidebar from '@/components/AppSidebar.vue';

import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb';

import { Separator } from '@/components/ui/separator';

import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

import { type BreadcrumbItem } from '@/types';

interface Props {
    className?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Password settings',
        href: '/settings/password',
    },
];

const passwordInput = ref<HTMLInputElement>();
const currentPasswordInput = ref<HTMLInputElement>();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: (errors: any) => {
            if (errors.password) {
                form.reset('password', 'password_confirmation');
                if (passwordInput.value instanceof HTMLInputElement) {
                    passwordInput.value.focus();
                }
            }

            if (errors.current_password) {
                form.reset('current_password');
                if (currentPasswordInput.value instanceof HTMLInputElement) {
                    currentPasswordInput.value.focus();
                }
            }
        },
    });
};
</script>

<template>
    <Head title="Password settings" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <!-- HEADER -->
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem class=""> Settings </CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <!-- SETTINGS PAGE -->
            <div class="p-6">
                <SettingsLayout>
                    <div class="space-y-6">
                        <HeadingSmall title="Update password" description="Ensure your account is using a long, random password to stay secure" />

                        <form @submit.prevent="updatePassword" class="space-y-6">
                            <!-- CURRENT PASSWORD -->
                            <div class="grid gap-2">
                                <Label for="current_password">Current Password</Label>

                                <Input
                                    id="current_password"
                                    ref="currentPasswordInput"
                                    v-model="form.current_password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    autocomplete="current-password"
                                    placeholder="Current password"
                                />

                                <InputError :message="form.errors.current_password" />
                            </div>

                            <!-- NEW PASSWORD -->
                            <div class="grid gap-2">
                                <Label for="password">New password</Label>

                                <Input
                                    id="password"
                                    ref="passwordInput"
                                    v-model="form.password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    autocomplete="new-password"
                                    placeholder="New password"
                                />

                                <InputError :message="form.errors.password" />
                            </div>

                            <!-- CONFIRM PASSWORD -->
                            <div class="grid gap-2">
                                <Label for="password_confirmation">Confirm password</Label>

                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full"
                                    autocomplete="new-password"
                                    placeholder="Confirm password"
                                />

                                <InputError :message="form.errors.password_confirmation" />
                            </div>

                            <!-- SAVE BUTTON -->
                            <div class="flex items-center gap-4">
                                <Button :disabled="form.processing"> Save password </Button>

                                <TransitionRoot
                                    :show="form.recentlySuccessful"
                                    enter="transition ease-in-out"
                                    enter-from="opacity-0"
                                    leave="transition ease-in-out"
                                    leave-to="opacity-0"
                                >
                                    <p class="text-sm text-neutral-600">Saved.</p>
                                </TransitionRoot>
                            </div>
                        </form>
                    </div>
                </SettingsLayout>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>
