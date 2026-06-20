<script setup lang="ts">
import { computed } from 'vue';

interface User {
    id: string | number;
    name: string;
    avatar_url?: string | null;
}

const props = defineProps<{
    users: User[];
    max?: number;
}>();

const displayUsers = computed(() => (props.users ?? []).slice(0, props.max ?? 3));

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const avatarColors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'];
const getAvatarColor = (name: string) => avatarColors[name.charCodeAt(0) % avatarColors.length];
</script>

<template>
    <div class="flex w-24 min-w-[5.5rem] shrink-0 items-center justify-end">
        <div class="flex justify-end -space-x-1.5">
            <div
                v-for="user in displayUsers"
                :key="user.id"
                class="flex h-6 w-6 items-center justify-center overflow-hidden rounded-full text-xs text-white ring-2 ring-white dark:ring-surface-900"
                :style="{ backgroundColor: getAvatarColor(user.name) }"
                :title="user.name"
            >
                <img v-if="user.avatar_url" :src="user.avatar_url" class="h-full w-full object-cover" />
                <span v-else>{{ getInitials(user.name) }}</span>
            </div>
        </div>
    </div>
</template>
