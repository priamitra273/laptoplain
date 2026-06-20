<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { Emoji, EmojiIndex } from 'emoji-mart-vue-fast/src';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import { ref, watch } from 'vue';
import type { ShellMember, ShellProject, SlimUser } from '@/pages/project-lazy';

interface Props {
    project: ShellProject;
    members: ShellMember[];
    canEdit: boolean;
    isMember: boolean;
}

interface Emits {
    (e: 'update', value: string, field: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const editMode = ref(false);
const localTitle = ref(props.project.title);
const emojiIndex = new EmojiIndex(emojiData);

watch(
    () => props.project.title,
    (newTitle) => {
        localTitle.value = newTitle;
    },
);

const enableEdit = () => {
    if (props.canEdit) {
        editMode.value = true;
    }
};

const onTitleBlur = () => {
    if (localTitle.value !== props.project.title && localTitle.value.trim() !== '') {
        emit('update', localTitle.value, 'title');
    } else {
        localTitle.value = props.project.title;
    }
    editMode.value = false;
};

const cancelEdit = () => {
    localTitle.value = props.project.title;
    editMode.value = false;
};

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const getMemberColor = (index: number) => {
    const colors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#6366f1', '#f43f5e'];
    return colors[index % colors.length];
};

const isUserHasAvatar = (user: SlimUser): boolean => {
    return !!user.avatar_url && user.avatar_url !== '/images/default-avatar.png';
};

const getUserAvatarImage = (user: SlimUser): string | undefined => {
    return isUserHasAvatar(user) ? (user.avatar_url as string) : undefined;
};

const getUserAvatarLabel = (user: SlimUser): string | undefined => {
    if (!isUserHasAvatar(user)) return getInitials(user.name);
    return undefined;
};

const getUserAvatarStyle = (user: SlimUser, index: number): object => {
    return !isUserHasAvatar(user) ? { backgroundColor: getMemberColor(index), color: 'white', fontSize: '1.25rem', fontWeight: '600' } : {};
};
</script>

<template>
    <div class="flex items-center justify-between border-b border-surface-200 pb-4 dark:border-surface-700">
        <div class="flex items-center gap-3">
            <Button
                icon="pi pi-arrow-left"
                text
                rounded
                severity="secondary"
                @click="router.get(route('project.index'))"
                class="hover:bg-surface-100 dark:hover:bg-surface-800"
            />
            <Emoji v-if="project?.emoji?.startsWith(':')" :data="emojiIndex" :emoji="project.emoji" set="google" :size="36" />
            <span v-else class="text-4xl">{{ project?.emoji }}</span>
            <div class="flex-1">
                <div
                    v-if="!editMode"
                    @click="enableEdit"
                    :class="canEdit ? '-mx-2 -my-1 cursor-pointer rounded px-2 py-1 hover:bg-surface-50 dark:hover:bg-surface-800' : ''"
                >
                    <h1 class="text-2xl font-semibold text-surface-900 dark:text-surface-0">{{ project.title }}</h1>
                </div>
                <div v-else class="flex items-center gap-2" @click.stop>
                    <InputText
                        v-model="localTitle"
                        class="text-2xl font-semibold"
                        @blur="onTitleBlur"
                        @keyup.enter="onTitleBlur"
                        @keyup.escape="cancelEdit"
                        autofocus
                    />
                </div>
                <p class="text-sm text-surface-600 dark:text-surface-400">
                    Software project
                    <span v-if="isMember" class="ml-2 text-green-600 dark:text-green-400"><i class="pi pi-check-circle"></i> Member</span>
                    <span v-else class="ml-2 text-gray-500 dark:text-gray-400"><i class="pi pi-eye"></i> Viewer</span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <AvatarGroup v-if="members.length > 0">
                <Avatar
                    v-for="(member, index) in members.slice(0, 3)"
                    :key="member.id"
                    :image="getUserAvatarImage(member.user)"
                    :label="getUserAvatarLabel(member.user)"
                    size="large"
                    shape="circle"
                    :style="getUserAvatarStyle(member.user, index)"
                    :title="member.user.name"
                    class="border-3 border-white dark:border-surface-900"
                />
                <Avatar
                    v-if="members.length > 3"
                    :label="`+${members.length - 3}`"
                    size="large"
                    shape="circle"
                    style="background-color: #64748b; color: white; font-size: 1.25rem; font-weight: 600"
                    class="border-3 border-white dark:border-surface-900"
                />
            </AvatarGroup>
        </div>
    </div>
</template>
