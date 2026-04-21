<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
type User = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    department?: { name: string } | null;
};
type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};
type PaginatedUsers = {
    data: User[];
    links: PaginationLink[];
};

const props = defineProps<{
    faculties: PaginatedUsers;
}>();
const faculties = computed(() => props.faculties.data);
const paginationLinks = computed(() => props.faculties.links);
const goToPage = (url: string | null) => {
    if (!url) return;

    router.get(
        url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

/* ================= INITIALS ================= */

const getInitials = (first: string, last: string) => {
    return `${first.charAt(0)}${last.charAt(0)}`.toUpperCase();
};
</script>

<template>
    <SidebarProvider>
        <AppSidebar />
        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-4">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>Department Faculty</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>
            <p>  Placeholder</p>
        </SidebarInset>
    </SidebarProvider>
</template>
