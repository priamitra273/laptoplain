<script setup lang="ts">
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import Card from 'primevue/card';
import type { User } from '../../index.d.ts';

interface Props {
    values: User[];
}

const props = defineProps<Props>();

const DEFAULT_IMAGE = '/images/default-avatar.png';

const hasAvatar = (value: User): boolean => {
    return !!value.avatar_url && value.avatar_url !== DEFAULT_IMAGE;
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const getUserColor = (index: number) => `hsl(${index * 60}, 70%, 60%)`;

const getAvatarImage = (value: User) => {
    return hasAvatar(value) ? (value.avatar_url as string) : undefined;
};

const getAvatarLabel = (value: User) => {
    return !hasAvatar(value) ? getInitials(value.name) : undefined;
};

const getAvatarStyle = (index: number, value: User) => {
    const styles: Record<string, string> = {};

    if (!hasAvatar(value)) {
        styles.backgroundColor = getUserColor(index);
        styles.color = 'white';
        styles.fontWeight = '600';
    }

    return styles;
};
</script>

<template>
    <Card class="rounded-2xl border-0 shadow-lg transition-shadow hover:shadow-xl">
        <template #title>
            <div class="flex items-center gap-2">
                <i class="pi pi-users text-green-500"></i>
                <h2 class="text-lg font-bold">Team Members</h2>
            </div>
        </template>
        <template #content>
            <div v-if="props.values.length" class="space-y-4">
                <AvatarGroup>
                    <Avatar
                        v-for="(user, idx) in props.values.slice(0, 5)"
                        :key="user.id"
                        :image="getAvatarImage(user)"
                        :label="getAvatarLabel(user)"
                        shape="circle"
                        size="large"
                        class="border-2 border-white shadow-md dark:border-gray-800"
                        :style="getAvatarStyle(idx, user)"
                        v-tooltip.bottom="user.name"
                    />

                    <Avatar
                        v-if="props.values.length > 5"
                        :label="`+${props.values.length - 5}`"
                        shape="circle"
                        size="large"
                        class="border-2 border-white bg-gray-300 shadow-md dark:border-gray-800"
                    />
                </AvatarGroup>
            </div>
            <p v-else class="text-sm italic text-gray-400">No members assigned</p>
        </template>
    </Card>
</template>
