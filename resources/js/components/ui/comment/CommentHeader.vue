<script setup lang="ts">
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import { computed, useTemplateRef } from 'vue';
import { HeaderProps, HeaederEmits } from './type';

const props = defineProps<HeaderProps>();
const emit = defineEmits<HeaederEmits>();

const menu = useTemplateRef('menu');

const menuItems = computed(() => {
    const items: MenuItem[] = [];

    if (props.currentLevel < 1) {
        items.push({
            label: 'Reply',
            icon: 'pi pi-reply',
            command: () => emit('reply', props.comment.id),
        });
    }

    if (String(props.comment.user?.id) === String(props.currentUserId)) {
        items.push(
            {
                label: 'Edit',
                icon: 'pi pi-pencil',
                command: () => emit('edit', props.comment),
            },
            {
                label: 'Delete',
                icon: 'pi pi-trash',
                command: () => emit('delete', props.comment.id),
            },
        );
    }
    return items;
});

const formattedTime = computed(() => moment(props.comment.created_at).fromNow());

const isEdited = computed(() => !!props.comment.updated_at && props.comment.updated_at !== props.comment.created_at);
</script>

<template>
    <div class="flex items-center justify-between gap-2">
        <div class="flex min-w-0 flex-1 items-center gap-1.5">
            <span class="truncate text-sm font-semibold text-surface-900 dark:text-surface-50">
                {{ comment.user?.name }}
            </span>
            <span class="text-surface-300 dark:text-surface-600" aria-hidden="true">·</span>
            <span class="whitespace-nowrap text-xs text-surface-400 dark:text-surface-500">
                {{ formattedTime }}
            </span>
            <span v-if="isEdited" class="whitespace-nowrap text-xs text-surface-400 dark:text-surface-500"> · diedit </span>
        </div>

        <Button
            v-if="menuItems.length > 0"
            icon="pi pi-ellipsis-h"
            text
            rounded
            size="small"
            aria-label="Comment actions"
            class="!h-7 !w-7 shrink-0 !text-surface-400 opacity-0 transition-opacity hover:!bg-surface-100 focus-visible:opacity-100 group-hover:opacity-100 max-sm:opacity-100 dark:!text-surface-500 dark:hover:!bg-surface-700"
            @click="menu?.toggle($event)"
        />

        <Menu ref="menu" :model="menuItems" :popup="true" />
    </div>
</template>
