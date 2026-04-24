<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader } from '@/components/ui/sidebar';
import type { NavItem as BaseNavItem, PageProps } from '@/types';
import { Link, usePage, useRemember } from '@inertiajs/vue3';
import { Archive, Calendar1, ChevronDown, FileText, FolderClosed, LayoutGrid, List, Search, Upload, Users } from 'lucide-vue-next';
import { computed } from 'vue';

type ExtendedUser = {
    user_type?: number | string | null;
    is_dept_chair?: boolean;
    is_focal_person?: boolean;
};

type RememberedSidebarState = {
    sidebarOpen: boolean;
    othersOpen: boolean;
};

type SidebarNavItem = BaseNavItem & {
    href: string;
};

const page = usePage<PageProps>();

const user = computed<ExtendedUser | null>(() => {
    return (page.props.auth?.user as ExtendedUser) ?? null;
});

const userType = computed<number>(() => Number(user.value?.user_type ?? 0));
const isDeptChair = computed<boolean>(() => user.value?.is_dept_chair === true);
const isFocalPerson = computed<boolean>(() => user.value?.is_focal_person === true);

const showOthersSidebar = computed<boolean>(() => {
    return userType.value === 1 && (isDeptChair.value || isFocalPerson.value);
});

const rememberedState = useRemember<RememberedSidebarState>(
    {
        sidebarOpen: true,
        othersOpen: true,
    },
    'sidebar-state',
);

const mainNavItems = computed<SidebarNavItem[]>(() => {
    if (userType.value === 1) {
        return [
            {
                title: 'Overview',
                href: route('faculty.dashboard'),
                icon: LayoutGrid,
            },
            {
                title: 'Projects',
                href: route('faculty.projects'),
                icon: FolderClosed,
            },
            {
                title: 'Defense Schedules',
                href: route('faculty.schedules'),
                icon: Calendar1,
            },
          
            {
                title: 'Browse Papers',
                href: route('resources.thesis'),
                icon: Search,
            },
        ];
    }

    return [
        {
            title: 'Overview',
            href: route('student.dashboard'),
            icon: LayoutGrid,
        },
        {
            title: 'My Submissions',
            href: route('student.submissions'),
            icon: FolderClosed,
        },
        {
            title: 'Forms and Templates',
            href: route('student.forms'),
            icon: FileText,
        },
        {
            title: 'Submit Manuscript',
            href: route('student.submit-final-manuscript'),
            icon: Upload,
        },
        {
            title: 'Browse Papers',
            href: route('resources.thesis'),
            icon: Search,
        },
    ];
});

const othersNavItems = computed<SidebarNavItem[] | null>(() => {
    if (!showOthersSidebar.value) return null;

    const items: SidebarNavItem[] = [];

    if (isDeptChair.value) {
        items.push(
            {
                title: 'Department Faculty',
                href: route('departmentchair.assignfaculty'),
                icon: Users,
            },
            {
                title: 'Research Archive',
                href: route('departmentchair.researcharchives'),
                icon: Archive,
            },
        );
    }

    if (isFocalPerson.value) {
        items.push(
            {
                title: 'List of Projects',
                href: route('focalperson.listofprojects'),
                icon: List,
            },
            {
                title: 'Forms and Templates',
                href: route('focalperson.forms.templates'),
                icon: FileText,
            },
        );
    }

    return items.length ? items : null;
});

const toggleOthers = (): void => {
    rememberedState.othersOpen = !rememberedState.othersOpen;
};

const normalizeUrl = (url: string): string => {
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.pathname.replace(/\/+$/, '') || '/';
    } catch {
        return url.replace(/\/+$/, '') || '/';
    }
};

const isActive = (item: SidebarNavItem): boolean => {
    const currentPath = normalizeUrl(page.url);
    const itemPath = normalizeUrl(item.href);

    return currentPath === itemPath || currentPath.startsWith(`${itemPath}/`);
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" :default-open="rememberedState.sidebarOpen" @update:open="rememberedState.sidebarOpen = $event">
        <SidebarHeader class="flex h-28 items-center justify-center">
            <Link :href="route('home')" class="flex items-center justify-center">
                <AppLogo class="h-20 w-auto object-contain transition-transform duration-200 hover:scale-105" />
            </Link>
        </SidebarHeader>

        <SidebarContent class="space-y-1 px-2">
            <div class="space-y-1">
                <Link
                    v-for="item in mainNavItems"
                    :key="item.title"
                    :href="item.href"
                    preserve-scroll
                    preserve-state
                    class="group relative flex h-12 items-center rounded-xl px-4 transition-all duration-200 group-data-[state=collapsed]:justify-center group-data-[state=collapsed]:px-0"
                    :class="{
                        'bg-gray-200 group-data-[state=collapsed]:bg-transparent': isActive(item),
                        'hover:bg-gray-100 group-data-[state=collapsed]:hover:bg-transparent': !isActive(item),
                    }"
                >
                    <div v-if="isActive(item)" class="absolute left-0 hidden h-6 w-1 rounded-r bg-gray-400 group-data-[state=collapsed]:block" />

                    <component :is="item.icon" class="h-5 w-5 shrink-0" :class="isActive(item) ? 'text-gray-900' : 'text-gray-600'" />

                    <span class="ml-3 whitespace-nowrap text-sm font-medium group-data-[state=collapsed]:hidden">
                        {{ item.title }}
                    </span>
                </Link>
            </div>

            <div v-if="othersNavItems" class="pt-0">
                <button
                    type="button"
                    @click="toggleOthers"
                    class="group relative flex h-12 w-full items-center rounded-xl px-4 transition-all duration-200 hover:bg-gray-100 group-data-[state=collapsed]:justify-center group-data-[state=collapsed]:px-0 group-data-[state=collapsed]:hover:bg-transparent"
                >
                    <span class="ml-3 whitespace-nowrap text-sm font-semibold text-gray-700 group-data-[state=collapsed]:hidden"> Others </span>

                    <ChevronDown
                        class="ml-auto h-4 w-4 transition-transform group-data-[state=collapsed]:ml-0"
                        :class="{ 'rotate-180': rememberedState.othersOpen }"
                    />
                </button>

                <div v-show="rememberedState.othersOpen" class="mt-0.5 space-y-1">
                    <Link
                        v-for="item in othersNavItems"
                        :key="item.title"
                        :href="item.href"
                        preserve-scroll
                        preserve-state
                        class="group relative flex h-11 items-center rounded-xl px-4 transition hover:bg-gray-100 group-data-[state=collapsed]:justify-center group-data-[state=collapsed]:px-0"
                        :class="{
                            'bg-gray-200 group-data-[state=collapsed]:bg-transparent': isActive(item),
                            'hover:bg-gray-100 group-data-[state=collapsed]:hover:bg-transparent': !isActive(item),
                        }"
                    >
                        <component :is="item.icon" class="h-5 w-5 shrink-0" :class="isActive(item) ? 'text-gray-900' : 'text-gray-600'" />

                        <span class="ml-3 whitespace-nowrap text-sm font-medium group-data-[state=collapsed]:hidden">
                            {{ item.title }}
                        </span>
                    </Link>
                </div>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
