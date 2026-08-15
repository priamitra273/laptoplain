<script setup lang="ts">
import UserAvatar from '@/components/UserAvatar.vue';
import moment from 'moment';
import type { Task, User } from '../../index.d.ts';
import SectionPanel from './SectionPanel.vue';

interface Props {
    task: Task;
    creator?: User;
}

const props = defineProps<Props>();
</script>

<template>
    <SectionPanel>
        <template #header>
            <div class="flex w-full items-center justify-between gap-3">
                <h2 class="sr-only">Description</h2>
                <div class="flex min-w-0 items-center gap-2.5">
                    <UserAvatar v-if="props.creator" :user="props.creator" size="!h-9 !w-9" fontSize="0.75rem" />
                    <span
                        v-else
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-200 text-surface-500 dark:bg-surface-700 dark:text-surface-300"
                    >
                        <i class="pi pi-user text-sm" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-surface-900 dark:text-surface-50">{{ props.creator?.name ?? 'Unknown' }}</p>
                        <p class="text-xs text-surface-500 dark:text-surface-400">{{ moment(props.task.created_at).fromNow() }}</p>
                    </div>
                </div>
            </div>
        </template>

        <div
            v-if="props.task.description"
            class="prose prose-sm dark:prose-invert max-w-none break-words text-surface-700 dark:text-surface-300"
            v-html="props.task.description"
        />
        <p v-else class="text-sm italic text-surface-500 dark:text-surface-400">No description provided.</p>
    </SectionPanel>
</template>
