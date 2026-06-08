<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Breadcrumb from 'primevue/breadcrumb';
import Button from 'primevue/button';
import Card from 'primevue/card';
import { computed } from 'vue';

import 'emoji-mart-vue-fast/css/emoji-mart.css';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { Emoji, EmojiIndex } from 'emoji-mart-vue-fast/src';
import { MenuItem } from 'primevue/menuitem';
import type { Project, Task } from '../../index.d.ts';

const emojiIndex = new EmojiIndex(emojiData);

interface Props {
    project: Project;
    task: Task;
}

const props = defineProps<Props>();

const breadcrumbItems = computed<MenuItem[]>(() => [
    {
        label: 'Projects',
        icon: 'pi pi-folder',
        command: () => router.visit(route('project.index')),
    },
    {
        label: props.project.title,
        icon: 'pi pi-folder-open',
        command: () => router.visit(route('project.show', { encoded: props.project.id })),
    },
    {
        label: props.task.title,
        icon: 'pi pi-file',
    },
]);

const breadcrumbHome = {
    icon: 'pi pi-home',
    command: () => router.visit(route('dashboard')),
};

const goToProject = () => {
    if (props.project?.id) {
        router.visit(route('project.show', { encoded: props.project.id }));
    }
};
</script>

<template>
    <div class="flex flex-col gap-6">
        <Card class="rounded-2xl border-0 shadow-md">
            <template #content>
                <Breadcrumb :home="breadcrumbHome" :model="breadcrumbItems" class="border-none bg-transparent p-0 text-sm">
                    <template #item="{ item, props }">
                        <a
                            v-bind="props.action"
                            class="flex cursor-pointer items-center gap-1.5 text-gray-500 transition-colors hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                        >
                            <i :class="item.icon" class="text-xs"></i>
                            <span class="max-w-prose truncate font-medium" :title="item.label as string">
                                {{ item.label }}
                            </span>
                        </a>
                    </template>
                </Breadcrumb>
            </template>
        </Card>

        <Card class="overflow-hidden rounded-2xl border-0 bg-gradient-to-br from-blue-50 to-indigo-50 shadow-lg dark:from-gray-800 dark:to-gray-900">
            <template #content>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <Button
                        icon="pi pi-arrow-left"
                        text
                        rounded
                        severity="secondary"
                        @click="router.visit(route('project.show', { encoded: project.id }))"
                        class="hover:bg-surface-100 dark:hover:bg-surface-800"
                    />
                    <div class="flex cursor-pointer items-start gap-4 transition-transform hover:scale-[1.02]" @click="goToProject">
                        <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-white shadow-md dark:bg-gray-800">
                            <Emoji
                                v-if="props.project?.emoji?.startsWith(':')"
                                :data="emojiIndex"
                                :emoji="props.project.emoji"
                                set="google"
                                :size="36"
                            />
                            <span v-else class="text-4xl">
                                {{ props.project.emoji }}
                            </span>
                        </div>
                        <div class="flex-1">
                            <h1 class="mb-1 break-all text-3xl font-bold text-gray-800 dark:text-white">
                                {{ props.task.title }}
                            </h1>
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <i class="pi pi-folder text-blue-500"></i>
                                <span>Project:</span>
                                <span class="font-semibold text-blue-600 dark:text-blue-400">
                                    {{ props.project.title }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
