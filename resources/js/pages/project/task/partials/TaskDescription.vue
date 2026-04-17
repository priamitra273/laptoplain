<script setup lang="ts">
import { useLayout } from '@/composables/useLayouts.js';
import { getInitials } from '@/lib/utils.js';
import moment from 'moment';
import type { Task, User } from '../../index.d.ts';

interface Props {
    task: Task;
    creator?: User;
}

const props = defineProps<Props>();

const { isDarkTheme } = useLayout();
</script>

<template>
    <Panel class="overflow-hidden !rounded-2xl shadow-sm" pt:header:class="!border-b">
        <template #header>
            <div class="flex w-full items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Avatar :image="props.creator?.avatar_url ?? undefined" :label="getInitials(props.creator?.name ?? '')" shape="circle" />
                    <h2 class="font-bold">{{ props.creator?.name }}</h2>
                </div>

                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ moment(props.task.created_at).fromNow() }}
                </span>
            </div>
        </template>
        <template #default>
            <div
                class="pt-4 text-gray-700 dark:text-gray-300"
                v-html="props.task.description || '<p class=\'text-gray-400 italic\'>No description provided</p>'"
            />
        </template>
    </Panel>
</template>
