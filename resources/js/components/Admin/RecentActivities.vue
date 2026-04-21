<script setup lang="ts">
import { Activity, CheckCircle2, FolderKanban, GraduationCap, RefreshCcw, UserPlus } from 'lucide-vue-next';
import { computed } from 'vue';

type ActivityItem = {
    id: number;
    name: string; // 👈 ADD THIS
    title: string;
    description: string;
    type: 'assignment' | 'user' | 'department' | 'system' | 'project';
    time: string;
    date: string;
};

const props = defineProps<{
    activities?: ActivityItem[];
}>();

const displayActivities = computed(() => (props.activities || []).slice(0, 5));

const getIcon = (type: string) => {
    switch (type) {
        case 'assignment':
            return CheckCircle2;
        case 'user':
            return UserPlus;
        case 'department':
            return GraduationCap;
        case 'system':
            return RefreshCcw;
        case 'project':
            return FolderKanban;
        default:
            return Activity;
    }
};

const getBadgeClass = (type: string) => {
    switch (type) {
        case 'assignment':
            return 'bg-blue-100 text-blue-600';
        case 'user':
            return 'bg-green-100 text-green-600';
        case 'department':
            return 'bg-purple-100 text-purple-600';
        case 'system':
            return 'bg-yellow-100 text-yellow-600';
        case 'project':
            return 'bg-indigo-100 text-indigo-600';
        default:
            return 'bg-gray-100 text-gray-600';
    }
};

const getTypeLabel = (type: string) => {
    switch (type) {
        case 'assignment':
            return 'Completed';
        case 'user':
            return 'User';
        case 'department':
            return 'Department';
        case 'system':
            return 'System';
        case 'project':
            return 'Project';
        default:
            return 'Activity';
    }
};
</script>

<template>
    <div class="rounded-2xl border bg-white shadow">
        <div class="flex items-center justify-between border-b px-6 py-4">
            <div class="flex items-center gap-2">
                <Activity class="h-5 w-5 text-green-600" />
                <h3 class="text-lg font-semibold">Recent Activities</h3>
            </div>
        </div>

        <div v-if="displayActivities.length">
            <div
                v-for="activity in displayActivities"
                :key="activity.id"
                class="flex items-start gap-4 border-b px-6 py-5 transition-all duration-200 last:border-none hover:bg-gray-50"
            >
                <!-- ICON -->
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100">
                    <component :is="getIcon(activity.type)" class="h-5 w-5 text-gray-600" />
                </div>

                <!-- CONTENT -->
                <div class="flex-1">
                    <!-- TITLE -->
                    <p class="font-semibold text-gray-900">
                        {{ activity.title }}
                    </p>

                    <!-- 👇 NAME + DESCRIPTION (FIX HERE) -->
                    <p class="mt-1 text-sm text-gray-600">
                        <span v-if="activity.name" class="font-medium text-gray-800">
                            {{ activity.name }}
                        </span>
                        <span v-if="activity.name" class="ml-1">
                            {{ activity.description }}
                        </span>
                        <span v-else>
                            {{ activity.description }}
                        </span>
                    </p>

                    <!-- DATE -->
                    <p class="mt-2 text-xs text-gray-400">{{ activity.time }} • {{ activity.date }}</p>
                </div>

                <!-- BADGE -->
                <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="getBadgeClass(activity.type)">
                    {{ getTypeLabel(activity.type) }}
                </span>
            </div>
        </div>

        <div v-else class="p-6 text-center text-sm text-gray-500">No activities found.</div>
    </div>
</template>
