<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps as InertiaPageProps } from '@inertiajs/core'
import { ChevronDown, Menu, X } from 'lucide-vue-next'
import { computed, ref } from 'vue'

interface AuthUser {
    id?: number
    name?: string
    email?: string
}

interface CustomPageProps extends InertiaPageProps {
    auth?: {
        user?: AuthUser | null
    }
}

const page = usePage<CustomPageProps>()

const user = computed(() => page.props.auth?.user ?? null)

const isMobileMenuOpen = ref(false)
const isResourcesOpen = ref(true)

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value

    if (isMobileMenuOpen.value) {
        isResourcesOpen.value = true
    }
}
</script>

<template>
    <header class="relative z-50 bg-white py-4 pl-2 pr-6 shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between">
            <div class="flex items-center">
                <Link href="/" class="flex items-center gap-2">
                    <img
                        src="/images/coonect.png"
                        alt="Connect-Shelf Logo"
                        class="mr-1 h-[80px] w-[100px] object-contain"
                    />

                    <div class="leading-tight">
                        <div class="flex items-center gap-1 text-[25px]">
                            <span class="text-[#0C4B05]">Connect-</span>
                            <span class="text-[#FFCD00]">Shelf</span>
                        </div>

                        <div class="text-[13px] text-black">
                            Repository &amp; Management Portal
                        </div>
                    </div>
                </Link>
            </div>

            <nav class="hidden items-center gap-8 lg:flex">
                <Link href="/" class="group relative py-2 text-[18px] text-black">
                    Home
                    <span class="absolute -bottom-1 left-0 h-1 w-0 bg-[#FFCD00] transition-all duration-300 group-hover:w-full"></span>
                </Link>

                <div class="group relative">
                    <button class="group relative flex items-center gap-1 px-3 py-2 text-[18px] text-black">
                        Resources Available
                        <ChevronDown class="h-4 w-4 transition-transform group-hover:rotate-180" />
                        <span class="absolute -bottom-1 left-0 h-1 w-0 bg-[#FFCD00] transition-all duration-300 group-hover:w-full"></span>
                    </button>

                    <div
                        class="invisible absolute top-full z-50 mt-2 w-56 overflow-hidden rounded-xl border border-gray-100 bg-white opacity-0 shadow-lg transition-all duration-300 group-hover:visible group-hover:opacity-100"
                    >
                        <Link :href="route('resources.thesis')" class="block px-4 py-3 hover:bg-gray-200">
                            Thesis Projects
                        </Link>

                        <Link :href="route('resources.capstone')" class="block px-4 py-3 hover:bg-gray-200">
                            Capstone Projects
                        </Link>
                    </div>
                </div>

                <Link :href="route('resources.about')" class="group relative py-2 text-[18px] text-black">
                    About
                    <span class="absolute -bottom-1 left-0 h-1 w-0 bg-[#FFCD00] transition-all duration-300 group-hover:w-full"></span>
                </Link>
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                <template v-if="user">
                    <Link
                        :href="route('dashboard')"
                        class="rounded-sm border border-[#19140035] px-6 py-2.5 text-sm text-[#1b1b18]"
                    >
                        Dashboard
                    </Link>
                </template>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="group relative px-6 py-2.5 text-[18px] text-black"
                    >
                        Log in
                        <span class="absolute -bottom-1 left-0 h-1 w-0 bg-[#FFCD00] transition-all duration-300 group-hover:w-full"></span>
                    </Link>

                    <Link
                        :href="route('register')"
                        class="rounded-xl bg-[#0C4B05] px-6 py-2.5 text-[18px] text-white shadow-lg transition-all duration-300 hover:scale-105 hover:bg-[#0C4B05]/90"
                    >
                        Register
                    </Link>
                </template>
            </div>

            <button class="text-gray-700 hover:text-[#0C4B05] lg:hidden" @click="toggleMobileMenu">
                <X v-if="isMobileMenuOpen" class="h-6 w-6" />
                <Menu v-else class="h-6 w-6" />
            </button>
        </div>

        <div v-if="isMobileMenuOpen" class="w-full bg-white shadow-xl lg:hidden">
            <nav class="flex flex-col gap-1 p-4">
                <Link class="block rounded-xl px-4 py-3 text-black hover:bg-gray-200" href="/">
                    Home
                </Link>

                <button
                    @click="isResourcesOpen = !isResourcesOpen"
                    class="flex items-center justify-between rounded-xl px-4 py-3 text-black hover:bg-gray-200"
                >
                    Resources Available
                    <ChevronDown
                        class="h-4 w-4 transition-transform"
                        :class="{ 'rotate-180': isResourcesOpen }"
                    />
                </button>

                <div v-if="isResourcesOpen" class="ml-4 flex flex-col gap-1">
                    <Link :href="route('resources.thesis')" class="block rounded-xl px-4 py-3 hover:bg-gray-200">
                        Thesis Projects
                    </Link>

                    <Link :href="route('resources.capstone')" class="block rounded-xl px-4 py-3 hover:bg-gray-200">
                        Capstone Projects
                    </Link>
                </div>

                <Link :href="route('resources.about')" class="block rounded-xl px-4 py-3 hover:bg-gray-200">
                    About
                </Link>

                <div class="mt-3 pt-3">
                    <template v-if="user">
                        <Link
                            :href="route('dashboard')"
                            class="block rounded-xl px-4 py-3 hover:bg-gray-200"
                        >
                            Dashboard
                        </Link>
                    </template>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="block rounded-xl px-4 py-3 text-black hover:bg-gray-200"
                        >
                            Log in
                        </Link>

                        <Link
                            :href="route('register')"
                            class="mt-2 block rounded-xl bg-[#0C4B05] px-4 py-3 text-center text-white hover:bg-[#0C4B05]/90"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </nav>
        </div>
    </header>
</template>