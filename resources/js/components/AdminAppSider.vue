<script setup lang="ts">
import NavUser from '@/components/AdminNavUser.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import { Building2, ChevronDown, ChevronUp, LayoutGrid, List, Settings, UserCog, Users, UserX } from 'lucide-vue-next';

import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarRail } from '@/components/ui/sidebar';

type SharedUser = {
    id?: number;
    first_name?: string;
    middle_name?: string | null;
    last_name?: string;
    extension_name?: string | null;
    full_name?: string;
    email?: string;
    user_type?: number;
    roles?: string[];
};

type ZiggyPageProps = {
    ziggy?: {
        route?: {
            name?: string;
        };
    };
    auth?: {
        user?: SharedUser | null;
        admin?: SharedUser | null;
    };
};

const page = usePage<ZiggyPageProps>();

const currentRoute = computed(() => {
    return page.props.ziggy?.route?.name ?? '';
});

const isRouteActive = (routes: string[]) => {
    return routes.some((routeName) => {
        const cleanRoute = routeName.replace('.*', '');
        return currentRoute.value === cleanRoute || currentRoute.value.startsWith(cleanRoute + '.');
    });
};

const userSubItems = [
    {
        key: 'users-list',
        title: 'List of Users',
        href: route('admin.users'),
        icon: List,
        routes: ['admin.users'],
    },
    {
        key: 'users-assign-role',
        title: 'Assign Role',
        href: route('admin.users.assign-role'),
        icon: UserCog,
        routes: ['admin.users.assign-role'],
    },
    {
        key: 'users-deactivate',
        title: 'Deactivate User',
        href: route('admin.users.deactivate.index'),
        icon: UserX,
        routes: ['admin.users.deactivate.index', 'admin.users.toggle-active', 'admin.users.deactivate.submit', 'admin.users.reactivate.submit'],
    },
];

const USER_MENU_STORAGE_KEY = 'admin-sidebar-users-menu-open';
const SELECTED_MENU_STORAGE_KEY = 'admin-sidebar-selected-menu';
const SELECTED_SUBMENU_STORAGE_KEY = 'admin-sidebar-selected-submenu';

const getStoredValue = (key: string, fallback = '') => {
    if (typeof window === 'undefined') return fallback;
    return window.localStorage.getItem(key) ?? fallback;
};

const isOverviewRouteActive = computed(() => isRouteActive(['admin.dashboard']));
const isDepartmentsRouteActive = computed(() => isRouteActive(['admin.departments', 'admin.departments.store']));
const isSettingsRouteActive = computed(() => isRouteActive(['admin.settings', 'admin.settings.update-profile', 'admin.settings.update-password']));

const selectedMenu = ref(getStoredValue(SELECTED_MENU_STORAGE_KEY));
const selectedSubMenu = ref(getStoredValue(SELECTED_SUBMENU_STORAGE_KEY));

const activeUserRouteKey = computed(() => {
    const matchedItem = userSubItems.find((item) => isRouteActive(item.routes));
    return matchedItem?.key ?? '';
});

const activeUserSubMenuKey = computed(() => {
    return activeUserRouteKey.value || selectedSubMenu.value;
});

const getInitialUserMenuState = () => {
    if (typeof window === 'undefined') {
        return !!activeUserSubMenuKey.value || selectedMenu.value === 'users';
    }

    const savedState = window.localStorage.getItem(USER_MENU_STORAGE_KEY);

    if (savedState !== null) {
        return savedState === 'true';
    }

    return !!activeUserSubMenuKey.value || selectedMenu.value === 'users';
};

const userMenuOpen = ref(getInitialUserMenuState());

const setSelectedMenu = (menuKey: string) => {
    selectedMenu.value = menuKey;

    if (menuKey !== 'users') {
        selectedSubMenu.value = '';
    }
};

const setSelectedSubMenu = (menuKey: string, subMenuKey: string) => {
    selectedMenu.value = menuKey;
    selectedSubMenu.value = subMenuKey;

    if (menuKey === 'users') {
        userMenuOpen.value = true;
    }
};

const toggleUsersMenu = () => {
    userMenuOpen.value = !userMenuOpen.value;
    selectedMenu.value = 'users';

    if (!userMenuOpen.value) {
        selectedSubMenu.value = '';
    }
};

const isOverviewActive = computed(() => {
    return isOverviewRouteActive.value || selectedMenu.value === 'overview';
});

const isUsersActive = computed(() => {
    return selectedMenu.value === 'users' && !activeUserSubMenuKey.value;
});

const isDepartmentsActive = computed(() => {
    return isDepartmentsRouteActive.value || selectedMenu.value === 'departments';
});

const isSettingsActive = computed(() => {
    return isSettingsRouteActive.value || selectedMenu.value === 'settings';
});

const isSubItemActive = (item: (typeof userSubItems)[number]) => {
    return activeUserSubMenuKey.value === item.key;
};

watch(
    activeUserRouteKey,
    (value) => {
        if (value) {
            selectedMenu.value = 'users';
            selectedSubMenu.value = value;
            userMenuOpen.value = true;
        }
    },
    { immediate: true },
);

watch(
    userMenuOpen,
    (value) => {
        if (typeof window !== 'undefined') {
            window.localStorage.setItem(USER_MENU_STORAGE_KEY, String(value));
        }
    },
    { immediate: true },
);

watch(
    selectedMenu,
    (value) => {
        if (typeof window !== 'undefined') {
            window.localStorage.setItem(SELECTED_MENU_STORAGE_KEY, value);
        }
    },
    { immediate: true },
);

watch(
    selectedSubMenu,
    (value) => {
        if (typeof window !== 'undefined') {
            window.localStorage.setItem(SELECTED_SUBMENU_STORAGE_KEY, value);
        }
    },
    { immediate: true },
);

const sidebarUser = computed(() => {
    const source = page.props.auth?.admin ?? page.props.auth?.user;

    const fullName = source?.full_name
        ? source.full_name
        : [source?.first_name ?? '', source?.middle_name ?? '', source?.last_name ?? '', source?.extension_name ?? '']
              .filter((value) => value && String(value).trim() !== '')
              .join(' ')
              .trim();

    return {
        name: fullName || '',
        email: source?.email || '',
        avatar: null,
    };
});
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        class="group [&_[data-active=true]]:border-l-4 [&_[data-active=true]]:border-gray-400 [&_[data-active=true]]:bg-gray-200 [&_[data-active=true]]:text-gray-900 [&_[data-active=true]]:hover:bg-gray-200"
    >
        <SidebarHeader class="px-2 pb-4 pt-6">
            <div class="hidden w-full items-center justify-center group-data-[state=expanded]:flex group-data-[state=collapsed]:hidden">
                <img src="/images/coonect.png" alt="Logo" class="h-20 object-contain" />
            </div>

            <div class="hidden items-center justify-center group-data-[state=collapsed]:flex group-data-[state=expanded]:hidden">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl">
                    <img src="/images/coonect.png" alt="Logo" class="h-10 w-10 object-contain transition-all duration-200" />
                </div>
            </div>
        </SidebarHeader>

        <SidebarContent class="px-2">
            <div class="mt-2">
                <Link
                    :href="route('admin.dashboard')"
                    :data-active="isOverviewActive"
                    @click="setSelectedMenu('overview')"
                    class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-base font-medium text-gray-700 transition hover:bg-gray-100 group-data-[state=collapsed]:justify-center"
                >
                    <LayoutGrid class="h-5 w-5 shrink-0" />
                    <span class="group-data-[state=collapsed]:hidden">Overview</span>
                </Link>
            </div>

            <div class="mt-2">
                <button
                    type="button"
                    @click="toggleUsersMenu"
                    :data-active="isUsersActive"
                    class="flex w-full items-center justify-between rounded-lg px-4 py-3 text-base font-medium text-gray-700 transition hover:bg-gray-100 group-data-[state=collapsed]:justify-center"
                >
                    <div class="flex items-center gap-3 group-data-[state=collapsed]:justify-center">
                        <Users class="h-5 w-5 shrink-0" />
                        <span class="group-data-[state=collapsed]:hidden">Users</span>
                    </div>

                    <span class="group-data-[state=collapsed]:hidden">
                        <ChevronUp v-if="userMenuOpen" class="h-5 w-5" />
                        <ChevronDown v-else class="h-5 w-5" />
                    </span>
                </button>

                <div v-if="userMenuOpen" class="ml-5 mt-2 space-y-1 border-l border-gray-200 pl-4 group-data-[state=collapsed]:hidden">
                    <template v-for="item in userSubItems" :key="item.key">
                        <Link
                            v-if="item.href !== '#'"
                            :href="item.href"
                            :data-active="isSubItemActive(item)"
                            @click="setSelectedSubMenu('users', item.key)"
                            class="flex items-center gap-3 rounded-lg px-4 py-3 text-[15px] font-medium text-gray-700 transition hover:bg-gray-100"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            <span>{{ item.title }}</span>
                        </Link>

                        <a
                            v-else
                            href="#"
                            :data-active="isSubItemActive(item)"
                            @click.prevent="setSelectedSubMenu('users', item.key)"
                            class="flex items-center gap-3 rounded-lg px-4 py-3 text-[15px] font-medium text-gray-700 transition hover:bg-gray-100"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            <span>{{ item.title }}</span>
                        </a>
                    </template>
                </div>
            </div>

            <div class="mt-2">
                <Link
                    :href="route('admin.departments')"
                    :data-active="isDepartmentsActive"
                    @click="setSelectedMenu('departments')"
                    class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-base font-medium text-gray-700 transition hover:bg-gray-100 group-data-[state=collapsed]:justify-center"
                >
                    <Building2 class="h-5 w-5 shrink-0" />
                    <span class="group-data-[state=collapsed]:hidden">Departments</span>
                </Link>
            </div>

            <div class="mt-2">
                <Link
                    v-if="(route() as any).has('admin.settings')"
                    :href="route('admin.settings')"
                    :data-active="isSettingsActive"
                    @click="setSelectedMenu('settings')"
                    class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-base font-medium text-gray-700 transition hover:bg-gray-100 group-data-[state=collapsed]:justify-center"
                >
                    <Settings class="h-5 w-5 shrink-0" />
                    <span class="group-data-[state=collapsed]:hidden">Settings</span>
                </Link>

                <a
                    v-else
                    href="#"
                    :data-active="isSettingsActive"
                    @click.prevent="setSelectedMenu('settings')"
                    class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-base font-medium text-gray-700 transition hover:bg-gray-100 group-data-[state=collapsed]:justify-center"
                >
                    <Settings class="h-5 w-5 shrink-0" />
                    <span class="group-data-[state=collapsed]:hidden">Settings</span>
                </a>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavUser :user="sidebarUser" />
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
</template>
