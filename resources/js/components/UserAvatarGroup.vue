<script setup lang="ts">
import { getInitials } from '@/lib/utils';

interface AvatarUser {
    id: string;
    name: string;
    avatar_url?: string | null;
}

withDefaults(
    defineProps<{
        users: AvatarUser[];
        max?: number;
        size?: 'xs' | 'sm' | 'md' | 'lg';
        emptyLabel?: string;
    }>(),
    { max: 3, size: 'xs' },
);
</script>

<template>
    <UAvatarGroup v-if="users.length" :max="max" :size="size">
        <UAvatar
            v-for="user in users"
            :key="user.id"
            :src="user.avatar_url ?? undefined"
            :alt="user.name"
            :title="user.name"
            :text="getInitials(user.name)"
        />
    </UAvatarGroup>

    <span v-else-if="emptyLabel" class="text-muted text-xs">{{ emptyLabel }}</span>
</template>
