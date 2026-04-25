<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import { severityClasses } from '@/lib/severity';
import { watchDebounced } from '@vueuse/core';
import axios from 'axios';
import _ from 'lodash';
import { onMounted, ref } from 'vue';
import { Project } from '..';

import Sprint = App.Data.Sprint;

const props = defineProps<{
    sprintId: string;
    project: Project;
}>();

const loading = ref(false);
const sprintTasks = ref<Sprint.SprintStatusReportData>({ completed_tasks: [], incomplete_tasks: [] });

const fetchSprintTasks = async (sprintId: string) => {
    loading.value = true;

    const response = await axios.get(
        route('sprints.status-report', {
            project: props.project.id,
            projectSprint: sprintId,
        }),
    );

    sprintTasks.value = response.data.data;
    loading.value = false;
};

watchDebounced(
    () => props.sprintId,
    (sprintId) => {
        if (sprintId) {
            fetchSprintTasks(sprintId);
        }
    },
    { debounce: 500 },
);

onMounted(() => {
    fetchSprintTasks(props.sprintId);
});
</script>

<template>
    <div v-for="(items, key) in sprintTasks" :key="key">
        <h3 class="mb-4 text-lg font-medium text-gray-700 dark:text-gray-100">{{ _.startCase(key) }}</h3>
        <DataTable :value="items" :loading="loading" showGridlines tableStyle="min-width: 50rem">
            <Column field="key" header="Key">
                <template #body="{ data }">
                    <a
                        :href="route('task.show', data.id)"
                        target="_blank"
                        class="group flex items-center gap-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        <span class="group-hover:underline">{{ data.key }}</span>
                        <Icon name="SquareArrowOutUpRight" class="size-4" />
                    </a>
                </template>
            </Column>

            <Column field="title" header="Title" style="width: 600px"></Column>

            <Column field="category.name" header="Work Type">
                <template #body="{ data }">
                    <span class="flex items-center gap-1">
                        <span :class="[data.category?.icon, severityClasses[data.category?.severity]?.headerText]"></span>
                        <span>{{ data.category?.name }}</span>
                    </span>
                </template>
            </Column>

            <Column field="rootAncestor.title" header="Epic">
                <template #body="{ data }">
                    <Tag class="!border !border-blue-500 !bg-transparent !p-1">
                        <div class="flex items-center gap-2 px-1">
                            <span class="text-sm text-blue-500 dark:text-blue-300">{{ data.rootAncestor?.title }}</span>
                        </div>
                    </Tag>
                </template>
            </Column>

            <Column field="status.name" header="Status">
                <template #body="{ data }">
                    <Tag class="!border !border-surface-500 !bg-transparent !p-1">
                        <div class="flex items-center gap-2 px-1">
                            <span class="text-sm text-surface-500 dark:text-surface-300">{{ data.status?.name }}</span>
                        </div>
                    </Tag>
                </template>
            </Column>

            <Column header="Assignee">
                <template #body="{ data }">
                    <AvatarGroup>
                        <UserAvatar v-for="user in data.users" :user="user" size="!size-8" v-tooltip="user.name" :key="user.id" />
                    </AvatarGroup>
                </template>
            </Column>

            <Column field="story_points" header="Story points"></Column>

            <template #empty>
                <div class="flex flex-col items-center justify-center gap-4 text-gray-500 dark:text-gray-400">
                    <span>No {{ _.startCase(key) }}</span>
                </div>
            </template>
        </DataTable>
    </div>
</template>
