<script setup lang="ts">
import type { LazyTaskFormProps } from '@/pages/project-lazy';
import { useTaskForm } from '../composables/useTaskForm';
import InputAttachment from './form-ui/InputAttachment.vue';
import InputDateRange from './form-ui/InputDateRange.vue';
import InputDescription from './form-ui/InputDescription.vue';
import InputTags from './form-ui/InputTags.vue';
import InputTitle from './form-ui/InputTitle.vue';
import SelectArchivedProgress from './form-ui/SelectArchivedProgress.vue';
import SelectCategory from './form-ui/SelectCategory.vue';
import SelectMembers from './form-ui/SelectMembers.vue';
import SelectParentTask from './form-ui/SelectParentTask.vue';
import SelectTypeStatusPriority from './form-ui/SelectTypeStatusPriority.vue';

const props = withDefaults(defineProps<LazyTaskFormProps>(), {
    taskCategories: () => [],
    excludeEpicCategory: false,
    onlyEpicCategory: false,
    hideParentTaskField: false,
    sprintId: null,
});

import type { SavedTaskPayload } from '@/pages/project-lazy';

const emit = defineEmits<{ (e: 'close'): void; (e: 'saved', payload: SavedTaskPayload): void }>();

const {
    form,
    processing,
    selectedMembers,
    selectedTags,
    validationErrors,
    selectedParentId,
    statusOption,
    categoryOptions,
    requiresDates,
    minDueDate,
    isInProgressStatus,
    isEdit,
    formattedMemberOption,
    tagOptions,
    fieldDisabled,
    onStatusChange,
    submit,
} = useTaskForm(props, emit);
</script>

<template>
    <div class="flex flex-col gap-4">
        <InputTitle
            v-model="form.title"
            :error="form.errors.title || validationErrors.title"
            :disabled="fieldDisabled('title')"
            @update:modelValue="() => delete validationErrors.title"
        />

        <InputDescription v-model="form.description" :error="form.errors.description" :disabled="fieldDisabled('description')" />

        <Divider />

        <SelectParentTask
            v-if="!props.hideParentTaskField"
            v-model="selectedParentId"
            :task="props.task"
            :tasks="props.tasks"
            :error="form.errors.parent_id"
            :disabled="fieldDisabled('parent_id')"
        />

        <SelectCategory
            v-if="categoryOptions.length > 0"
            v-model="form.task_category_id"
            :options="categoryOptions"
            :error="form.errors.task_category_id"
            :disabled="fieldDisabled('task_category_id')"
        />

        <SelectTypeStatusPriority
            v-model:typeId="form.type_id"
            v-model:statusId="form.status_id"
            v-model:priorityId="form.priority_id"
            :typeOptions="props.taskTypes"
            :statusOptions="statusOption"
            :priorityOptions="props.taskPriorities"
            :typeError="form.errors.type_id"
            :statusError="form.errors.status_id"
            :priorityError="form.errors.priority_id"
            :typeDisabled="fieldDisabled('type_id')"
            :priorityDisabled="fieldDisabled('priority_id')"
            @update:statusId="onStatusChange"
        />

        <SelectMembers v-model="selectedMembers" :options="formattedMemberOption" :disabled="fieldDisabled('assign_users')" />

        <InputDateRange
            v-model:startDate="form.start_date"
            v-model:dueDate="form.due_date"
            :disabled="fieldDisabled('start_date') || fieldDisabled('end_date')"
            :required="requiresDates"
            :minDueDate="minDueDate"
            :isInProgressStatus="isInProgressStatus"
            :startDateError="form.errors.start_date"
            :dueDateError="form.errors.due_date"
        />

        <InputTags
            v-model="selectedTags"
            :options="tagOptions"
            :error="Object.keys(form.errors).some((k) => k.startsWith('add_tag')) ? 'Invalid tag data.' : null"
            :disabled="fieldDisabled('tags')"
        />

        <Divider />

        <InputAttachment v-model="form.attachments" />

        <Divider />

        <SelectArchivedProgress v-model:isArchived="form.is_archived" :archivedDisabled="fieldDisabled('is_archived')" />

        <div class="sticky mt-4 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" @click="emit('close')" :disabled="processing" />
            <Button v-if="!isEdit" label="Create Task" @click="submit" icon="pi pi-save" :loading="processing" :disabled="processing" />
            <Button
                v-else
                label="Update Task"
                severity="warning"
                @click="submit"
                :loading="processing"
                :disabled="processing"
                icon="pi pi-save"
            />
        </div>
    </div>
</template>
