<script setup lang="ts">
import { User } from '@pages/project';
import Avatar from 'primevue/avatar';
import { computed } from 'vue';

interface Props {
    user?: User;
}

const props = defineProps<Props>();

const initials = computed(() => {
    if (!props.user?.name) return 'U';

    return props.user.name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

const userColor = computed(() => {
    const userId = Number(props.user?.id) || 0;
    return `hsl(${(userId * 60) % 360}, 70%, 60%)`;
});

const hasCustomAvatar = computed(() => {
    return props.user?.avatar_url && props.user.avatar_url !== '/images/default-avatar.png';
});
</script>

<template>
    <Avatar
        v-if="hasCustomAvatar"
        :image="user?.avatar_url!"
        size="normal"
        shape="circle"
        class="flex-shrink-0 border-2 border-white shadow-sm dark:border-gray-800"
        style="width: 32px; height: 32px"
    />
    <Avatar
        v-else
        :label="initials"
        size="normal"
        shape="circle"
        class="flex-shrink-0 border-2 border-white text-white shadow-sm dark:border-gray-800"
        :style="{
            backgroundColor: userColor,
            color: 'white',
            fontWeight: '600',
            width: '32px',
            height: '32px',
            fontSize: '0.75rem',
        }"
    />
</template>
