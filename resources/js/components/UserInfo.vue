<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface UserWithAvatar extends User {
    avatar?: string | null;
}

interface Props {
    user: UserWithAvatar;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Combine first and last name safely
const fullName = computed(() => {
    return `${props.user.first_name ?? ''} ${props.user.last_name ?? ''}`.trim();
});

// Compute whether we should show the avatar image
const showAvatar = computed(() => {
    return !!props.user.avatar && props.user.avatar.trim() !== '';
});
</script>

<template>
    <div class="flex items-center gap-3">
        <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
            <AvatarImage
                v-if="showAvatar"
                :src="props.user.avatar ?? ''"
                :alt="fullName"
            />
            <AvatarFallback class="rounded-lg text-black dark:text-white">
                {{ getInitials(fullName) }}
            </AvatarFallback>
        </Avatar>

        <div class="grid flex-1 text-left text-sm leading-tight">
            <span class="truncate font-medium">
                {{ props.user.first_name }} {{ props.user.last_name }}
            </span>
            <span v-if="props.showEmail" class="truncate text-xs text-muted-foreground">
                {{ props.user.email }}
            </span>
        </div>
    </div>
</template>