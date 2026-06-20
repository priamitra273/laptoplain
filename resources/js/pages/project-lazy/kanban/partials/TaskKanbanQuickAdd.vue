<script setup lang="ts">
import TaskPriorityIcon from '@/components/TaskPriorityIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import { onMounted, ref } from 'vue';
import type { SlimUser, TaskPriorityOption, TaskTypeOption } from '@/pages/project-lazy';

const props = defineProps<{
    statusId: string;
    taskTypes: TaskTypeOption[];
    taskPriorities: TaskPriorityOption[];
    userOptions: SlimUser[];
    currentUser?: SlimUser;
    isNeedDueDate?: boolean;
    loading?: boolean;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    cancel: [];
    submit: [formData: any];
    openFull: [statusId: string];
}>();

const quickAddTitleRef = ref<any>(null);

const form = ref({
    title: '',
    type_id: null as TaskTypeOption | null,
    priority_id: null as TaskPriorityOption | null,
    assign_users: [] as SlimUser[],
    start_date: null as Date | null,
    due_date: null as Date | null,
});

onMounted(() => {
    const currentUser = props.userOptions.find((user) => user.id === props.currentUser?.id);

    if (currentUser) {
        form.value.assign_users = [currentUser];
    }

    setTimeout(() => {
        if (quickAddTitleRef.value) {
            // It might be a PrimeVue component or a native element
            if (typeof quickAddTitleRef.value.focus === 'function') {
                quickAddTitleRef.value.focus();
            } else if (quickAddTitleRef.value.$el) {
                quickAddTitleRef.value.$el.focus();
            }
        }
    }, 50);
});

const submitForm = () => {
    emit('submit', form.value);
};
</script>

<template>
    <div class="mb-2 rounded-lg border border-blue-300 bg-white shadow-md dark:border-blue-700 dark:bg-surface-800">
        <div class="flex flex-col gap-2 p-2.5">
            <!-- Title -->
            <div>
                <Textarea
                    ref="quickAddTitleRef"
                    v-model="form.title"
                    placeholder="Task title…"
                    rows="2"
                    class="w-full !resize-none !text-sm"
                    :class="errors?.title ? '!border-rose-400' : ''"
                    @keydown.escape="emit('cancel')"
                    autoResize
                />
                <p v-if="errors?.title" class="mt-0.5 text-[10px] text-rose-500">{{ errors.title }}</p>
            </div>

            <!-- Type -->
            <div>
                <Select
                    v-model="form.type_id"
                    :options="taskTypes"
                    optionLabel="name"
                    placeholder="Type *"
                    class="w-full !text-xs"
                    :class="errors?.type_id ? '!border-rose-400' : ''"
                >
                    <template #value="{ value }">
                        <Tag v-if="value" :value="value.name" :severity="value.severity" class="!text-xs" />
                        <span v-else class="text-xs text-surface-400">Type *</span>
                    </template>
                    <template #option="{ option }">
                        <Tag :value="option.name" :severity="option.severity" class="!text-xs" />
                    </template>
                </Select>
                <p v-if="errors?.type_id" class="mt-0.5 text-[10px] text-rose-500">{{ errors.type_id }}</p>
            </div>

            <!-- Priority -->
            <div>
                <Select
                    v-model="form.priority_id"
                    :options="taskPriorities"
                    optionLabel="name"
                    placeholder="Priority *"
                    class="w-full !text-xs"
                    :class="errors?.priority_id ? '!border-rose-400' : ''"
                >
                    <template #value="{ value }">
                        <div v-if="value" class="flex items-center gap-1.5">
                            <TaskPriorityIcon :priority="value" />
                            <Tag :value="value.name" :severity="value.severity" class="!text-xs" />
                        </div>
                        <span v-else class="text-xs text-surface-400">Priority *</span>
                    </template>
                    <template #option="{ option }">
                        <div class="flex items-center gap-1.5">
                            <TaskPriorityIcon :priority="option" />
                            <Tag :value="option.name" :severity="option.severity" class="!text-xs" />
                        </div>
                    </template>
                </Select>
                <p v-if="errors?.priority_id" class="mt-0.5 text-[10px] text-rose-500">{{ errors.priority_id }}</p>
            </div>

            <!-- Assignees -->
            <div>
                <MultiSelect
                    v-model="form.assign_users"
                    :options="userOptions"
                    optionLabel="name"
                    placeholder="Assign to *"
                    class="w-full !text-xs"
                    :class="errors?.assign_users ? '!border-rose-400' : ''"
                    :maxSelectedLabels="2"
                    display="chip"
                >
                    <template #option="{ option }">
                        <div class="flex items-center gap-2">
                            <UserAvatar :user="option" size="!h-5 !w-5" fontSize=".6rem" />
                            <span class="text-xs">{{ option.name }}</span>
                        </div>
                    </template>
                </MultiSelect>
                <p v-if="errors?.assign_users" class="mt-0.5 text-[10px] text-rose-500">{{ errors.assign_users }}</p>
            </div>

            <!-- Start date -->
            <div>
                <DatePicker
                    v-model="form.start_date"
                    placeholder="Start date (optional)"
                    dateFormat="dd M yy"
                    class="w-full !text-xs"
                    :class="errors?.start_date ? '!border-rose-400' : ''"
                    showIcon
                    iconDisplay="input"
                />
                <p v-if="errors?.start_date" class="mt-0.5 text-[10px] text-rose-500">{{ errors.start_date }}</p>
            </div>

            <!-- Due date -->
            <div>
                <DatePicker
                    v-model="form.due_date"
                    :placeholder="isNeedDueDate ? 'Due date *' : 'Due date (optional)'"
                    dateFormat="dd M yy"
                    class="w-full !text-xs"
                    :class="errors?.due_date ? '!border-rose-400' : ''"
                    showIcon
                    iconDisplay="input"
                    :minDate="form.start_date ?? undefined"
                />
                <p v-if="errors?.due_date" class="mt-0.5 text-[10px] text-rose-500">{{ errors.due_date }}</p>
            </div>
        </div>

        <!-- Form actions -->
        <div class="flex items-center gap-1 border-t border-surface-100 px-2.5 py-2 dark:border-surface-700">
            <Button label="Create" size="small" :loading="loading" @click="submitForm" class="!text-xs" />
            <Button label="Cancel" size="small" severity="secondary" text @click="emit('cancel')" class="!text-xs" />
            <Button
                icon="pi pi-external-link"
                size="small"
                severity="secondary"
                text
                v-tooltip.top="'Open full form'"
                class="ml-auto !text-xs"
                @click="emit('openFull', statusId)"
            />
        </div>
    </div>
</template>
