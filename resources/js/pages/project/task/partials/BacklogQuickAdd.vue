<script setup lang="ts">
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import type { TaskCategory, TaskPriority, TaskType, User } from '../type';

interface QuickForm {
    title: string;
    type_id: TaskType | null;
    priority_id: TaskPriority | null;
    category_id: TaskCategory | null;
    assign_users: User[];
    due_date: Date | null;
}

const props = defineProps<{
    taskTypes: TaskType[];
    taskPriorities: TaskPriority[];
    taskCategories: TaskCategory[];
    assignableUsers: User[];
    form: QuickForm;
    errors: Record<string, string>;
    loading: boolean;
}>();

const emit = defineEmits<{
    'update:form': [form: QuickForm];
    submit: [];
    cancel: [];
}>();

const update = (key: keyof QuickForm, value: any) => emit('update:form', { ...props.form, [key]: value });

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const avatarColor = (id: string) => {
    const palette = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#f43f5e', '#3b82f6'];
    let h = 0;
    for (let i = 0; i < id.length; i++) h = (h * 31 + id.charCodeAt(i)) % palette.length;
    return palette[h];
};

const PRIORITY_ICON: Record<string, string> = {
    highest: 'pi-angle-double-up',
    high: 'pi-angle-up',
    medium: 'pi-minus',
    low: 'pi-angle-down',
    lowest: 'pi-angle-double-down',
};
const getPriorityIcon = (name?: string) => PRIORITY_ICON[name?.toLowerCase() ?? ''] ?? 'pi-minus';
const getPriorityColor = (sev?: string) => {
    if (sev === 'danger') return '#ef4444';
    if (sev === 'warn' || sev === 'warning') return '#f59e0b';
    if (sev === 'success') return '#10b981';
    return '#94a3b8';
};
</script>

<template>
    <div class="rounded-lg border border-blue-200 bg-white shadow-sm dark:border-blue-700/60 dark:bg-surface-800">
        <div class="flex flex-col gap-2.5 p-3">
            <!-- Title -->
            <div>
                <Textarea
                    :value="form.title"
                    @input="update('title', ($event.target as HTMLTextAreaElement).value)"
                    placeholder="Task title… (Ctrl+Enter to save)"
                    rows="2"
                    auto-resize
                    class="w-full !resize-none !text-sm"
                    :class="errors.title ? '!border-rose-400' : ''"
                    @keydown.escape="emit('cancel')"
                    @keydown.ctrl.enter="emit('submit')"
                />
                <p v-if="errors.title" class="mt-0.5 text-[10px] text-rose-500">{{ errors.title }}</p>
            </div>

            <!-- Category + Type + Priority row -->
            <div class="flex flex-wrap gap-2">
                <!-- Category -->
                <Select
                    :model-value="form.category_id"
                    @update:model-value="update('category_id', $event)"
                    :options="taskCategories"
                    option-label="name"
                    placeholder="Category"
                    class="flex-1 !text-xs"
                    style="min-width: 110px"
                >
                    <template #value="{ value }">
                        <span v-if="value" class="text-xs">{{ value.icon }} {{ value.name }}</span>
                        <span v-else class="text-xs text-surface-400">Category</span>
                    </template>
                    <template #option="{ option }">
                        <span class="text-xs">{{ option.icon }} {{ option.name }}</span>
                    </template>
                </Select>

                <!-- Type -->
                <Select
                    :model-value="form.type_id"
                    @update:model-value="update('type_id', $event)"
                    :options="taskTypes"
                    option-label="name"
                    placeholder="Type *"
                    class="flex-1 !text-xs"
                    style="min-width: 100px"
                    :class="errors.type_id ? '!border-rose-400' : ''"
                >
                    <template #value="{ value }">
                        <Tag v-if="value" :value="value.name" :severity="value.severity" class="!text-xs" />
                        <span v-else class="text-xs text-surface-400">Type *</span>
                    </template>
                    <template #option="{ option }">
                        <Tag :value="option.name" :severity="option.severity" class="!text-xs" />
                    </template>
                </Select>

                <!-- Priority -->
                <Select
                    :model-value="form.priority_id"
                    @update:model-value="update('priority_id', $event)"
                    :options="taskPriorities"
                    option-label="name"
                    placeholder="Priority *"
                    class="flex-1 !text-xs"
                    style="min-width: 110px"
                    :class="errors.priority_id ? '!border-rose-400' : ''"
                >
                    <template #value="{ value }">
                        <div v-if="value" class="flex items-center gap-1">
                            <i :class="`pi ${getPriorityIcon(value.name)} text-xs`" :style="`color:${getPriorityColor(value.severity)}`" />
                            <span class="text-xs">{{ value.name }}</span>
                        </div>
                        <span v-else class="text-xs text-surface-400">Priority *</span>
                    </template>
                    <template #option="{ option }">
                        <div class="flex items-center gap-1.5">
                            <i :class="`pi ${getPriorityIcon(option.name)} text-xs`" :style="`color:${getPriorityColor(option.severity)}`" />
                            <span class="text-xs">{{ option.name }}</span>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Inline validation errors -->
            <div v-if="errors.type_id || errors.priority_id" class="flex gap-3">
                <p v-if="errors.type_id" class="text-[10px] text-rose-500">{{ errors.type_id }}</p>
                <p v-if="errors.priority_id" class="text-[10px] text-rose-500">{{ errors.priority_id }}</p>
            </div>

            <!-- Assignees + Due date row -->
            <div class="flex flex-wrap gap-2">
                <!-- Assignees -->
                <MultiSelect
                    :model-value="form.assign_users"
                    @update:model-value="update('assign_users', $event)"
                    :options="assignableUsers"
                    option-label="name"
                    placeholder="Assign to"
                    :max-selected-labels="2"
                    display="chip"
                    class="flex-1 !text-xs"
                    style="min-width: 140px"
                >
                    <template #option="{ option }">
                        <div class="flex items-center gap-2">
                            <Avatar
                                :image="option.avatar_url && option.avatar_url !== '/images/default-avatar.png' ? option.avatar_url : undefined"
                                :label="
                                    !option.avatar_url || option.avatar_url === '/images/default-avatar.png' ? getInitials(option.name) : undefined
                                "
                                shape="circle"
                                :style="`background:${avatarColor(option.id)};color:white;font-size:.55rem;font-weight:600`"
                                class="!h-5 !w-5"
                            />
                            <span class="text-xs">{{ option.name }}</span>
                        </div>
                    </template>
                </MultiSelect>

                <!-- Due date -->
                <DatePicker
                    :model-value="form.due_date"
                    @update:model-value="update('due_date', $event)"
                    placeholder="Due date"
                    date-format="dd M yy"
                    show-icon
                    icon-display="input"
                    class="flex-1 !text-xs"
                    style="min-width: 130px"
                />
            </div>
        </div>

        <!-- Form footer -->
        <div class="flex items-center gap-1 border-t border-surface-100 px-3 py-2 dark:border-surface-700">
            <Button label="Create" size="small" :loading="loading" @click="emit('submit')" class="!text-xs" />
            <Button label="Cancel" size="small" severity="secondary" text @click="emit('cancel')" class="!text-xs" />
        </div>
    </div>
</template>
