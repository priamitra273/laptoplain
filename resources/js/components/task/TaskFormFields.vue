<script setup lang="ts">
import DatePicker from '@/components/form/DatePicker.vue';
import FieldLabel from '@/components/ui/FieldLabel.vue';
import InputAttachment from '@/components/form/InputAttachment.vue';
import BadgeSelect from '@/components/form/BadgeSelect.vue';
import RichTextEditor from '@/components/form/RichTextEditor.vue';
import { formatDate } from '@/lib/date';
import { formErrorFor, getInitials, severityColor } from '@/lib/utils';
import { statusRequiresDueDate } from '@/lib/statusRules';
import { useParentPicker, type ParentPickerOption } from '@/composables/useParentPicker';
import type { PrimeSeverity, TaskFormModel, TaskOption, TaskOptionUser, UploadedFile } from '@/types';
import { parseDate } from '@internationalized/date';
import { computed, ref, watch } from 'vue';

const model = defineModel<TaskFormModel>({ required: true });

const props = defineProps<{
    statuses: TaskOption[];
    priorities: TaskOption[];
    types: TaskOption[];
    categories: TaskOption[];
    tags: TaskOption[];
    assignableUsers: TaskOptionUser[];
    parents: ParentPickerOption[];
    errors: Record<string, string | string[] | undefined>;
    disabled?: boolean;
}>();

/**
 * Satu-satunya tempat model ditulis. Objeknya diganti utuh, bukan diubah propertinya,
 * supaya tidak melanggar `vue/no-mutating-props`.
 */
const field = <K extends keyof TaskFormModel>(key: K) =>
    computed({
        get: () => model.value[key],
        set: (value: TaskFormModel[K]) => {
            model.value = { ...model.value, [key]: value };
        },
    });

const title = field('title');
const description = field('description');
const parentId = field('parent_id');
const typeId = field('type_id');
const statusId = field('status_id');
const priorityId = field('priority_id');
const categoryId = field('task_category_id');
const availableCategories = computed(() => {
    if (!parentId.value) {
        return props.categories;
    }

    return props.categories.filter((category) => category.name.trim().toLowerCase() !== 'epic');
});

const selectedCategoryIsEpic = computed(() => {
    const selectedCategory = props.categories.find((category) => category.id === categoryId.value);

    return selectedCategory?.name.trim().toLowerCase() === 'epic';
});

watch(parentId, (nextParentId, previousParentId) => {
    if (nextParentId && nextParentId !== previousParentId && selectedCategoryIsEpic.value) {
        categoryId.value = undefined;
    }
});
const assignUsers = field('assign_users');
const tagIds = field('tags');
const newTags = field('newTags');
const attachmentsField = field('attachments');

const errorFor = (...prefixes: string[]) => formErrorFor(props.errors, ...prefixes);

const startDateField = field('start_date');
const dueDateField = field('due_date');

const startDate = computed({
    get: () => (startDateField.value ? parseDate(startDateField.value) : undefined),
    set: (value) => {
        startDateField.value = value?.toString() ?? '';
    },
});

const dueDate = computed({
    get: () => (dueDateField.value ? parseDate(dueDateField.value) : undefined),
    set: (value) => {
        dueDateField.value = value?.toString() ?? '';
    },
});

/** `InputAttachment` memakai `null` untuk "kosong", model ini memakai array kosong. */
const attachments = computed({
    get: () => attachmentsField.value,
    set: (value: (File | UploadedFile)[] | null) => {
        attachmentsField.value = value ?? [];
    },
});

const requiresDates = computed(
    () => !!statusId.value && statusRequiresDueDate(props.statuses.find((status) => status.id === statusId.value)?.name ?? ''),
);

const parentSearch = ref('');
const {
    items: parentItems,
    toggle: toggleParent,
    isBranch: isParentBranch,
    isExpanded: isParentExpanded,
} = useParentPicker(() => props.parents, parentSearch);

const selectedAssignees = computed(() => props.assignableUsers.filter((user) => assignUsers.value.includes(user.id)));
const selectedTags = computed(() => props.tags.filter((tag) => tagIds.value.includes(tag.id)));
const selectedTagCount = computed(() => selectedTags.value.length + newTags.value.length);

const removeAssignee = (id: string) => {
    assignUsers.value = assignUsers.value.filter((userId) => userId !== id);
};

const clearAssignees = () => {
    assignUsers.value = [];
};

const removeTag = (id: string) => {
    tagIds.value = tagIds.value.filter((tagId) => tagId !== id);
};

const removeNewTag = (name: string) => {
    newTags.value = newTags.value.filter((tag) => tag.name !== name);
};

/** Tag baru selalu dapat warna acak, mengikuti perilaku form lama (`InputTags.vue` di pages_v1). */
const TAG_SEVERITIES: PrimeSeverity[] = ['primary', 'secondary', 'success', 'info', 'warn', 'danger', 'contrast'];
const tagSearch = ref('');

const createTag = (name: string) => {
    const trimmed = name.trim();
    if (!trimmed) return;

    const existing = props.tags.find((tag) => tag.name.toLowerCase() === trimmed.toLowerCase());

    if (existing) {
        if (!tagIds.value.includes(existing.id)) tagIds.value = [...tagIds.value, existing.id];
    } else if (!newTags.value.some((tag) => tag.name.toLowerCase() === trimmed.toLowerCase())) {
        newTags.value = [...newTags.value, { name: trimmed, severity: TAG_SEVERITIES[Math.floor(Math.random() * TAG_SEVERITIES.length)] }];
    }

    tagSearch.value = '';
};
</script>

<template>
    <div class="flex flex-col gap-5">
        <UFormField label="Title" name="title" required :hint="`${title.length}/255`" :error="errorFor('title')">
            <UInput v-model="title" autofocus maxlength="255" placeholder="What needs to be done?" :disabled="disabled" class="w-full" />
        </UFormField>

        <UFormField label="Description" name="description" :error="errorFor('description')" :inert="disabled"
            ><RichTextEditor v-model="description"
        /></UFormField>

        <div class="flex flex-col gap-3">
            <div class="flex items-center gap-3">
                <FieldLabel title="Classification" class="shrink-0" />
                <USeparator class="flex-1" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <UFormField label="Type" name="type_id" required :error="errorFor('type_id')">
                    <BadgeSelect v-model="typeId" :items="types" placeholder="Select type" :disabled="disabled" class="w-full" />
                </UFormField>
                <UFormField label="Status" name="status_id" required :error="errorFor('status_id')">
                    <BadgeSelect v-model="statusId" :items="statuses" placeholder="Select status" :disabled="disabled" class="w-full" />
                </UFormField>
                <UFormField label="Priority" name="priority_id" required :error="errorFor('priority_id')">
                    <BadgeSelect
                        display="priority"
                        v-model="priorityId"
                        :items="priorities"
                        placeholder="Select priority"
                        :disabled="disabled"
                        class="w-full"
                    />
                </UFormField>
                <UFormField label="Category" name="task_category_id" :error="errorFor('task_category_id')">
                    <BadgeSelect
                        v-model="categoryId"
                        :items="availableCategories"
                        placeholder="Select category"
                        :disabled="disabled"
                        class="w-full"
                    />
                </UFormField>
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <div class="flex items-center gap-3">
                <FieldLabel title="Schedule" class="shrink-0" />
                <USeparator class="flex-1" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <UFormField label="Start date" name="start_date" :required="requiresDates" :error="errorFor('start_date')">
                    <DatePicker
                        v-model="startDate"
                        :label="startDate ? formatDate(model.start_date) : 'Select start date'"
                        trigger-aria-label="Select start date"
                        trigger-class="w-full"
                        clearable
                        :disabled="disabled"
                    />
                </UFormField>
                <UFormField label="Due date" name="due_date" :required="requiresDates" :error="errorFor('due_date')">
                    <DatePicker
                        v-model="dueDate"
                        :label="dueDate ? formatDate(model.due_date) : 'Select due date'"
                        trigger-aria-label="Select due date"
                        trigger-class="w-full"
                        clearable
                        :min-value="startDate"
                        :disabled="disabled"
                    />
                </UFormField>
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <FieldLabel title="People & context" class="shrink-0" />
                <USeparator class="flex-1" />
            </div>

            <UFormField
                label="Assignees"
                name="assign_users"
                :hint="`${assignUsers.length} of ${assignableUsers.length}`"
                :error="errorFor('assign_users', 'unassign_users')"
            >
                <USelectMenu
                    v-model="assignUsers"
                    :items="assignableUsers"
                    value-key="id"
                    label-key="name"
                    multiple
                    :disabled="disabled"
                    :ui="{ base: 'h-auto min-h-9 py-1.5' }"
                    class="w-full"
                >
                    <template #default>
                        <span v-if="!selectedAssignees.length" class="text-dimmed">Select people</span>
                        <span v-else-if="selectedAssignees.length > 4" class="flex min-w-0 items-center">
                            <UBadge color="neutral" variant="subtle" size="xs" class="gap-1 rounded-full">
                                {{ selectedAssignees.length }} people selected
                                <UButton
                                    as="span"
                                    icon="i-lucide-x"
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                    square
                                    tabindex="-1"
                                    aria-label="Remove all assignees"
                                    class="-me-1"
                                    @pointerdown.stop.prevent
                                    @click.stop.prevent="clearAssignees"
                                />
                            </UBadge>
                        </span>
                        <span v-else class="flex min-w-0 flex-1 flex-wrap items-center gap-1">
                            <UBadge
                                v-for="user in selectedAssignees"
                                :key="user.id"
                                color="neutral"
                                variant="subtle"
                                size="xs"
                                class="gap-1 rounded-full ps-0.5"
                            >
                                <UAvatar :alt="user.name" :text="getInitials(user.name)" size="3xs" />
                                <span class="max-w-24 truncate">{{ user.name }}</span>
                                <UButton
                                    as="span"
                                    icon="i-lucide-x"
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                    square
                                    tabindex="-1"
                                    :aria-label="`Remove ${user.name}`"
                                    class="-me-1"
                                    @pointerdown.stop.prevent
                                    @click.stop.prevent="removeAssignee(user.id)"
                                />
                            </UBadge>
                        </span>
                    </template>
                    <template #item-leading="{ item }">
                        <UAvatar :alt="item.name" :text="getInitials(item.name)" size="2xs" />
                    </template>
                </USelectMenu>
            </UFormField>

            <UFormField label="Tags" name="add_tag" :error="errorFor('add_tag', 'remove_tag')">
                <USelectMenu
                    v-model="tagIds"
                    v-model:search-term="tagSearch"
                    :items="tags"
                    value-key="id"
                    label-key="name"
                    multiple
                    create-item
                    :disabled="disabled"
                    :ui="{ base: 'h-auto min-h-9 py-1.5' }"
                    class="w-full"
                    @create="createTag"
                >
                    <template #default>
                        <span v-if="!selectedTagCount" class="text-dimmed">Add tag...</span>
                        <span v-else class="flex min-w-0 flex-1 flex-wrap items-center gap-1">
                            <UBadge
                                v-for="tag in selectedTags"
                                :key="tag.id"
                                :color="severityColor(tag.severity)"
                                variant="subtle"
                                size="xs"
                                class="gap-1 rounded-full"
                            >
                                <span class="max-w-24 truncate">{{ tag.name }}</span>
                                <UButton
                                    as="span"
                                    icon="i-lucide-x"
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                    square
                                    tabindex="-1"
                                    :aria-label="`Remove ${tag.name}`"
                                    class="-me-1"
                                    @pointerdown.stop.prevent
                                    @click.stop.prevent="removeTag(tag.id)"
                                />
                            </UBadge>
                            <UBadge
                                v-for="tag in newTags"
                                :key="tag.name"
                                :color="severityColor(tag.severity)"
                                variant="subtle"
                                size="xs"
                                class="gap-1 rounded-full"
                            >
                                <span class="max-w-24 truncate">{{ tag.name }}</span>
                                <UButton
                                    as="span"
                                    icon="i-lucide-x"
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                    square
                                    tabindex="-1"
                                    :aria-label="`Remove ${tag.name}`"
                                    class="-me-1"
                                    @pointerdown.stop.prevent
                                    @click.stop.prevent="removeNewTag(tag.name)"
                                />
                            </UBadge>
                        </span>
                    </template>
                </USelectMenu>
            </UFormField>

            <UFormField label="Parent task" name="parent_id" :error="errorFor('parent_id')">
                <div class="flex gap-2">
                    <USelectMenu
                        v-model="parentId"
                        v-model:search-term="parentSearch"
                        :items="parentItems"
                        value-key="id"
                        label-key="title"
                        icon="i-lucide-corner-down-right"
                        placeholder="No parent (top-level task)"
                        :disabled="disabled"
                        class="min-w-0 flex-1"
                    >
                        <template #item-label="{ item }">
                            <span class="flex min-w-0 items-center" :style="{ paddingInlineStart: `${item.depth * 1.25}rem` }">
                                <UButton
                                    v-if="isParentBranch(item.id)"
                                    :icon="isParentExpanded(item.id) ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'"
                                    :aria-label="`${isParentExpanded(item.id) ? 'Collapse' : 'Expand'} ${item.title}`"
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                    class="-ms-1 me-0.5 shrink-0"
                                    @pointerdown.stop.prevent
                                    @click.stop.prevent="toggleParent(item.id)"
                                />
                                <span v-else class="w-5 shrink-0" />
                                <span class="truncate">{{ item.title }}</span>
                            </span>
                        </template>
                    </USelectMenu>
                    <UButton
                        v-if="parentId"
                        icon="i-lucide-x"
                        aria-label="Clear parent"
                        color="neutral"
                        variant="ghost"
                        :disabled="disabled"
                        @click="parentId = undefined"
                    />
                </div>
            </UFormField>
        </div>

        <fieldset class="flex flex-col gap-3" :disabled="disabled" :class="disabled ? 'pointer-events-none opacity-60' : ''">
            <legend class="sr-only">Attachments</legend>
            <div class="flex items-center gap-3"><FieldLabel title="Attachments" class="shrink-0" /><USeparator class="flex-1" /></div>
            <UFormField name="attachments" :error="errorFor('attachments')">
                <InputAttachment v-model="attachments" :label="null" />
            </UFormField>
        </fieldset>

        <slot name="extra" />
    </div>
</template>
