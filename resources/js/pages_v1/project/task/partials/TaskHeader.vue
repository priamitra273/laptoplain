<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Breadcrumb from 'primevue/breadcrumb';
import { MenuItem } from 'primevue/menuitem';
import Skeleton from 'primevue/skeleton';
import { computed, onMounted, ref } from 'vue';

import 'emoji-mart-vue-fast/css/emoji-mart.css';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { Emoji, EmojiIndex } from 'emoji-mart-vue-fast/src';
import type { Project, Task } from '../../index.d.ts';

interface TaskParent {
    id: string;
    key: string;
    title: string;
    category?: { name?: string; icon?: string } | null;
}

const emojiIndex = new EmojiIndex(emojiData);

interface Props {
    project: Project;
    task: Task;
}

const props = defineProps<Props>();

const headerEmoji = computed(() => props.task.emoji || props.project.emoji || '');

const loadingParents = ref(true);
const breadcrumbItems = ref<MenuItem[]>([]);

const projectItem = (): MenuItem => ({
    label: props.project.title,
    icon: 'pi pi-folder',
    command: () => router.visit(route('project.show.kanban', { encoded: props.project.id })),
});

const fetchParents = async () => {
    loadingParents.value = true;

    try {
        const { data } = await axios.get(route('task.parents', { task: props.task.id }));
        const parents = (data?.data ?? []) as TaskParent[];

        const items: MenuItem[] = [projectItem()];

        if (parents.length) {
            // getParents() returns root → … → current task (current is last).
            parents.forEach((parent, index) => {
                const isCurrent = index === parents.length - 1;
                items.push({
                    label: parent.title || parent.key,
                    icon: parent.category?.icon ?? (isCurrent ? 'pi pi-file' : 'pi pi-sitemap'),
                    command: isCurrent ? undefined : () => router.visit(route('task.show', parent.id)),
                });
            });
        } else {
            items.push({ label: props.task.title, icon: 'pi pi-file' });
        }

        breadcrumbItems.value = items;
    } catch {
        breadcrumbItems.value = [projectItem(), { label: props.task.title, icon: 'pi pi-file' }];
    } finally {
        loadingParents.value = false;
    }
};

onMounted(fetchParents);
</script>

<template>
    <div class="flex flex-col gap-3 border-b border-surface-200 pb-4 dark:border-surface-700">
        <Skeleton v-if="loadingParents" width="22rem" height="1rem" />
        <Breadcrumb v-else :model="breadcrumbItems" :pt="{ root: '!overflow-x-auto !border-0 !bg-transparent !p-0' }">
            <template #item="{ item }">
                <button
                    v-if="item.command"
                    type="button"
                    class="flex items-center gap-1.5 rounded text-surface-500 transition-colors hover:text-primary-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-surface-400 dark:hover:text-primary-400"
                    @click="item.command?.({ originalEvent: $event, item })"
                >
                    <i v-if="item.icon" :class="item.icon" class="text-xs" />
                    <span class="max-w-[10rem] truncate">{{ item.label }}</span>
                </button>
                <span v-else class="flex items-center gap-1.5 font-medium text-surface-700 dark:text-surface-200">
                    <i v-if="item.icon" :class="item.icon" class="text-xs" />
                    <span class="max-w-[14rem] truncate">{{ item.label }}</span>
                </span>
            </template>
            <template #separator>
                <span class="text-surface-300 dark:text-surface-600">/</span>
            </template>
        </Breadcrumb>

        <div class="flex items-center gap-3">
            <span v-if="headerEmoji" class="shrink-0 leading-none" aria-hidden="true">
                <Emoji v-if="headerEmoji.startsWith(':')" :data="emojiIndex" :emoji="headerEmoji" set="google" :size="34" />
                <span v-else class="text-4xl leading-none">{{ headerEmoji }}</span>
            </span>
            <h1 class="min-w-0 break-words text-2xl font-semibold leading-tight tracking-tight text-surface-900 dark:text-surface-0">
                {{ props.task.title }}
            </h1>
        </div>
    </div>
</template>
