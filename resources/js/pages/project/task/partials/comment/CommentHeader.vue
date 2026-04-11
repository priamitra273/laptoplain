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
</script>

<template>
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
            <span class="text-xs font-semibold text-gray-900 dark:text-gray-100">
                {{ comment.user?.name }}
            </span>
            <span class="ml-1.5 text-xs text-gray-400 dark:text-gray-500">
                {{ formattedTime }}
            </span>
        </div>

        <Button
            v-if="menuItems.length > 0"
            icon="pi pi-ellipsis-v"
            text
            rounded
            size="small"
            class="h-6 w-6 text-gray-400 opacity-0 transition-opacity group-hover:opacity-100 dark:text-gray-500 dark:hover:bg-gray-700"
            @click="menu?.toggle($event)"
        />

        <Menu ref="menu" :model="menuItems" :popup="true" />
    </div>
</template>
