<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import type { PageProps as InertiaPageProps } from '@inertiajs/core'

import DeleteUser from '@/components/DeleteUser.vue'
import HeadingSmall from '@/components/HeadingSmall.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import SettingsLayout from '@/layouts/settings/Layout.vue'

import AppSidebar from '@/components/AppSidebar.vue'

import {
  Breadcrumb,
  BreadcrumbList,
  BreadcrumbItem as CrumbItem,
} from '@/components/ui/breadcrumb'

import { Separator } from '@/components/ui/separator'
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from '@/components/ui/sidebar'

import { computed } from 'vue'

interface User {
  id: number
  name: string
  email: string
  email_verified_at?: string | null
}

interface CustomPageProps extends InertiaPageProps {
  auth: {
    user: User
  }
  roles?: string[]
  user_type?: number
  is_dept_chair?: boolean
  is_focal_person?: boolean
}

const page = usePage<CustomPageProps>()

const user = computed<User>(() => page.props.auth.user)

const roles = computed<string[]>(() => page.props.roles ?? [])
const userType = computed<number | null>(() => page.props.user_type ?? null)
const isDeptChair = computed<boolean>(() => page.props.is_dept_chair ?? false)
const isFocalPerson = computed<boolean>(() => page.props.is_focal_person ?? false)

const showRoles = computed(() => userType.value !== 2)

const filteredRoles = computed<string[]>(() => {
  const unique = [...new Set<string>(roles.value)]

  return unique.filter((role) => {
    if (isDeptChair.value && role.toLowerCase().includes('chair')) {
      return false
    }
    return true
  })
})

const form = useForm({
  name: user.value?.name ?? '',
  email: user.value?.email ?? '',
})

const submit = () => {
  form.patch(route('profile.update'), {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Profile settings" />

  <SidebarProvider>
    <AppSidebar />

    <SidebarInset>
      <header class="flex h-16 items-center gap-2 border-b px-6">
        <SidebarTrigger />
        <Separator orientation="vertical" class="h-4" />

        <Breadcrumb>
          <BreadcrumbList>
            <CrumbItem>Settings</CrumbItem>
          </BreadcrumbList>
        </Breadcrumb>
      </header>

      <div class="p-6">
        <SettingsLayout>
          <div class="flex flex-col space-y-6">
            <HeadingSmall
              title="Profile information"
              description="Update your name and email address"
            />

            <form @submit.prevent="submit" class="space-y-6">
              <div class="grid gap-2">
                <Label>Name</Label>
                <Input v-model="form.name" required />
                <InputError :message="form.errors.name" />
              </div>

              <div class="grid gap-2">
                <Label>Email</Label>
                <Input v-model="form.email" type="email" required />
                <InputError :message="form.errors.email" />
              </div>

              <div
                v-if="showRoles"
                class="space-y-3 rounded-xl border p-4"
              >
                <h3 class="text-sm font-semibold text-gray-500">
                  Assigned Roles
                </h3>

                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="role in filteredRoles"
                    :key="role"
                    class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-600"
                  >
                    {{ role }}
                  </span>

                  <span
                    v-if="isDeptChair"
                    class="rounded-full bg-purple-50 px-3 py-1 text-xs font-medium text-purple-600"
                  >
                    Department Chair
                  </span>

                  <span
                    v-if="isFocalPerson"
                    class="rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-600"
                  >
                    Focal Person
                  </span>
                </div>

                <p
                  v-if="!filteredRoles.length && !isDeptChair && !isFocalPerson"
                  class="text-sm text-gray-400"
                >
                  No roles assigned.
                </p>
              </div>

              <div class="flex items-center gap-4">
                <Button :disabled="form.processing">Save</Button>

                <TransitionRoot :show="form.recentlySuccessful">
                  <p class="text-sm text-gray-500">Saved.</p>
                </TransitionRoot>
              </div>
            </form>

            <DeleteUser />
          </div>
        </SettingsLayout>
      </div>
    </SidebarInset>
  </SidebarProvider>
</template>