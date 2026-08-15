<script setup lang="ts">
import { User } from '@/pages/project';
import { computed } from 'vue';

interface Props {
    user: User;
    size?: string;
    fontSize?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: '!h-7 !w-7',
    fontSize: '.65rem',
});

const getInitials = (name: string) => {
    if (!name) return '';
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const avatarColor = (id: string) => {
    const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#f43f5e', '#3b82f6'];
    let h = 0;
    for (let i = 0; i < id.length; i++) {
        h = (h * 31 + id.charCodeAt(i)) % colors.length;
    }
    return colors[h] || colors[0];
};

const hasAvatar = computed(() => !!(props.user.avatar_url && props.user.avatar_url !== '/images/default-avatar.png'));
</script>

<template>
    <Avatar
        :image="hasAvatar ? props.user.avatar_url! : undefined"
        :label="!hasAvatar ? getInitials(props.user.name) : undefined"
        shape="circle"
        :style="{
            background: avatarColor(props.user.id as string),
            color: 'white',
            fontSize: fontSize,
            fontWeight: '600',
        }"
        :class="size"
        :title="props.user.name"
    />
</template>
