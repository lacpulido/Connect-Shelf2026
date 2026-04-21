<script setup lang="ts">
import AdminNotificationBell from '@/components/Admin/AdminNotificationBell.vue'
import AppSidebar from '@/components/AdminAppSider.vue'
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb'
import { Head, usePoll } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import DashboardKpis from '@/components/Admin/KpiCard.vue'
import RecentActivities from '@/components/Admin/RecentActivities.vue'

type ActivityItem = {
    id: number
    name: string
    title: string
    description: string
    type: 'assignment' | 'user' | 'department' | 'system' | 'project'
    time: string
    date: string
}

const props = defineProps<{
    totalUsers: number
    totalProjects: number
    ongoingProjects: number
    completedProjects: number
    activities: ActivityItem[]
}>()

const displayActivities = computed<ActivityItem[]>(() => props.activities ?? [])
const notificationCount = computed(() => displayActivities.value.length)

const openNotifications = () => {
    console.log('Opening notifications...')
}

// Poll recent activities every 5 seconds
usePoll(
    5000,
    {
        only: ['activities'],
    },
    {
        keepAlive: true,
    },
)

// Poll KPI data every 10 seconds
usePoll(
    10000,
    {
        only: [
            'totalUsers',
            'totalProjects',
            'ongoingProjects',
            'completedProjects',
        ],
    },
    {
        keepAlive: true,
    },
)
</script>

<template>
    <Head title="Overview" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>Overview</BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>

                <div class="ml-auto flex items-center">
                    <AdminNotificationBell :count="notificationCount" @click="openNotifications" />
                </div>
            </header>

            <div class="space-y-6 p-6">
                <DashboardKpis
                    :totalUsers="props.totalUsers"
                    :totalProjects="props.totalProjects"
                    :ongoingProjects="props.ongoingProjects"
                    :completedProjects="props.completedProjects"
                />

                <RecentActivities :activities="displayActivities" />
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>