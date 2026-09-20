<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import { Head } from '@inertiajs/vue3';
import type { MasterDataItem } from '../masterdata/types';
import TaskCategoryForm from './Form.vue';
import TaskCategoryTable from './Table.vue';

export interface TaskCategoryItem extends MasterDataItem {
    icon: string | null;
}

interface Props {
    task_categories?: TaskCategoryItem[];
}

withDefaults(defineProps<Props>(), {
    task_categories: () => [],
});

const overlay = useOverlay();

const categoryForm = overlay.create(TaskCategoryForm);

const addCategory = () => {
    categoryForm.open();
};

const editCategory = (value: TaskCategoryItem) => {
    categoryForm.open({ value });
};
</script>

<template>
    <Head title="Task Category" />

    <AppLayout title="Task Category">
        <Heading title="Task Category" description="Manage master data task category">
            <UButton v-if="can('task-category.create')" size="sm" @click="addCategory">Add Task Category</UButton>
        </Heading>

        <TaskCategoryTable :data="task_categories" @edit="editCategory" />
    </AppLayout>
</template>
