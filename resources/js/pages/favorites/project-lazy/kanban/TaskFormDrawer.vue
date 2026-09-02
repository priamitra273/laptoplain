<script setup lang="ts">
import RichTextEditor from '@/components/RichTextEditor.vue';
import TaskDueDateDialog from '@/components/TaskDueDateDialog.vue';
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { severityColor } from '@/lib/utils';
import { ProjectPolicyKey } from '@/types/type';
import { useHttp, usePage } from '@inertiajs/vue3';
import { DateFormatter, getLocalTimeZone, parseDate } from '@internationalized/date';
import { computed, inject, ref } from 'vue';
import type { KanbanBadge, KanbanStatusOption, KanbanTask, KanbanUser } from './types';

interface Props {
    task?: KanbanTask | null;
    projectId: string;
    sprintId?: string | null;
    defaultStatusId?: string;
    defaultParentId?: string | null;
    statuses?: KanbanStatusOption[];
    priorities?: KanbanBadge[];
    types?: KanbanBadge[];
    categories?: KanbanBadge[];
    assignableUsers?: KanbanUser[];
    tags?: KanbanBadge[];
    onlyEpicCategory?: boolean;
    excludeEpicCategory?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    task: null,
    sprintId: null,
    defaultStatusId: undefined,
    defaultParentId: null,
    statuses: () => [],
    priorities: () => [],
    types: () => [],
    categories: () => [],
    assignableUsers: () => [],
    tags: () => [],
    onlyEpicCategory: false,
    excludeEpicCategory: false,
});

const emits = defineEmits<{ close: [boolean] }>();

const toast = useToast();
const overlay = useOverlay();
const dueDateDialog = overlay.create(TaskDueDateDialog);
const page = usePage();
const currentUserId = computed(() => (page.props.auth as { user: { id: string } }).user.id);

const policy = inject(ProjectPolicyKey, null);
const { canUpdateTaskStatus } = useProjectPermissions(policy);

const isEdit = computed(() => !!props.task);
const title = computed(() => {
    if (isEdit.value) return 'Edit Task';
    return props.onlyEpicCategory ? 'Create Epic' : 'Create Task';
});

const description = computed(() => {
    if (isEdit.value) return 'Update the details of this task.';
    return props.onlyEpicCategory ? 'Epics group related tasks. They cannot have a parent or join a sprint.' : 'Add a task to this project.';
});

interface TaskFormData {
    title: string;
    description?: string;
    status_id?: string;
    priority_id?: string;
    type_id?: string;
    task_category_id?: string | null;
    parent_id?: string | null;
    start_date?: string;
    due_date?: string;
    assign_users?: string[];
    unassign_users?: string[];
    add_tag?: { exists: string[]; new: { name: string; severity: null }[] };
    remove_tag?: string[];
    attachments?: (File | { uuid: string })[];
    project_id?: string;
    sprint_id?: string;
    [key: string]: any;
}

const http = useHttp<TaskFormData>({
    title: '',
    description: '',
    status_id: undefined,
    priority_id: undefined,
    type_id: undefined,
    task_category_id: null,
    parent_id: null,
    start_date: '',
    due_date: '',
    assign_users: [],
    unassign_users: [],
    add_tag: { exists: [], new: [] },
    remove_tag: [],
    attachments: [],
    project_id: undefined,
    sprint_id: undefined,
});

const selectedUserIds = ref<string[]>([]);
const originalUserIds = ref<string[]>([]);

const tagStubs = ref<KanbanBadge[]>([]);
const tagItems = computed(() => [...props.tags, ...tagStubs.value]);
const selectedTagIds = ref<string[]>([]);
const originalTagIds = ref<string[]>([]);
const loadingDetail = ref(false);

interface ParentTaskOption {
    id: string;
    parent_id: string | null;
    title: string;
    category: { id: string; name: string } | null;
}

const parentOptions = ref<ParentTaskOption[]>([]);

const excludedParentIds = computed(() => {
    if (!props.task) return new Set<string>();

    const ids = new Set<string>([props.task.id]);
    const collectDescendants = (node: KanbanTask) => {
        for (const child of node.sub_task_recursive) {
            ids.add(child.id);
            collectDescendants(child);
        }
    };
    collectDescendants(props.task);

    return ids;
});

const availableParentOptions = computed(() => parentOptions.value.filter((option) => !excludedParentIds.value.has(option.id)));

interface ExistingAttachment {
    uuid: string;
    file_name: string;
    size: number;
    mime_type: string;
    url: string;
}

const existingAttachments = ref<ExistingAttachment[]>([]);
const newFiles = ref<File[]>([]);

const removeExistingAttachment = (uuid: string) => {
    existingAttachments.value = existingAttachments.value.filter((attachment) => attachment.uuid !== uuid);
};

const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const selectedStatusName = computed(() => props.statuses.find((s) => s.id === http.status_id)?.name);
const datesRequired = computed(() => !!selectedStatusName.value && !['To Do', 'Blocked'].includes(selectedStatusName.value));

const scheduleHint = computed(() => (datesRequired.value ? `A due date is required while the status is "${selectedStatusName.value}".` : undefined));

const dateFormatter = new DateFormatter('en-GB', { dateStyle: 'medium' });

const toCalendarDate = (value?: string | null) => (value ? parseDate(value.slice(0, 10)) : undefined);

/**
 * Klik pertama pada kalender rentang selalu mengembalikan `{ start, end: undefined }`.
 * Kalau langsung ditulis ke form, due date yang sudah terisi ikut terhapus — jadi
 * pilihan setengah jadi ditahan di draf (bentuk string, sama seperti form) dan baru
 * dipindahkan saat rentangnya lengkap.
 */
const scheduleDraft = ref<{ start: string; end: string } | null>(null);
const schedulePickerOpen = ref(false);

const schedule = computed({
    get: () => {
        const source = scheduleDraft.value ?? { start: http.start_date as string, end: http.due_date as string };

        return { start: toCalendarDate(source.start), end: toCalendarDate(source.end) };
    },
    set: (range) => {
        const start = range?.start?.toString() ?? '';
        const end = range?.end?.toString() ?? '';

        if (start && end) {
            http.start_date = start;
            http.due_date = end;
            scheduleDraft.value = null;
            schedulePickerOpen.value = false;
            return;
        }

        scheduleDraft.value = { start, end };
    },
});

// Menutup popover di tengah pemilihan membatalkan draf, bukan menyimpan separuh rentang.
const onSchedulePickerToggle = (open: boolean) => {
    schedulePickerOpen.value = open;
    if (!open) {
        scheduleDraft.value = null;
    }
};

const scheduleLabel = computed(() => {
    const { start, end } = schedule.value;

    if (!start) return 'Select dates';

    const startLabel = dateFormatter.format(start.toDate(getLocalTimeZone()));

    return end ? `${startLabel} - ${dateFormatter.format(end.toDate(getLocalTimeZone()))}` : startLabel;
});

const statusOptions = computed(() => props.statuses.filter((status) => canUpdateTaskStatus(status.id) || props.task?.status?.id === status.id));

const categoryOptions = computed(() => {
    if (props.onlyEpicCategory) return props.categories.filter((category) => category.name.toLowerCase() === 'epic');
    if (props.excludeEpicCategory) return props.categories.filter((category) => category.name.toLowerCase() !== 'epic');
    return props.categories;
});

const onCreateTag = (name: string) => {
    const trimmed = name.trim();
    if (!trimmed) return;

    const id = `new-${tagStubs.value.length}-${trimmed}`;
    tagStubs.value = [...tagStubs.value, { id, name: trimmed, severity: null }];
    selectedTagIds.value = [...selectedTagIds.value, id];
};

const fetchParentOptions = async () => {
    try {
        const response = await fetch(route('project.tasks.parent-options', { projectEncoded: props.projectId }), {
            headers: { Accept: 'application/json' },
        });
        const body = await response.json();
        parentOptions.value = body.data ?? [];
    } catch {
        toast.add({ title: 'Failed', description: 'Could not load parent task options.', color: 'error' });
    }
};

const initialize = async () => {
    http.clearErrors();
    tagStubs.value = [];
    selectedTagIds.value = [];
    originalTagIds.value = [];
    parentOptions.value = [];
    existingAttachments.value = [];
    newFiles.value = [];

    if (props.task) {
        http.title = props.task.title;
        http.description = props.task.description ?? '';
        http.status_id = props.task.status?.id;
        http.priority_id = props.task.priority?.id;
        http.type_id = props.task.type?.id;
        http.task_category_id = props.task.category?.id ?? null;
        http.parent_id = props.task.parent_id;
        http.start_date = props.task.start_date ?? '';
        http.due_date = props.task.due_date ?? '';
        selectedUserIds.value = props.task.users.map((user) => user.id);
        originalUserIds.value = [...selectedUserIds.value];

        loadingDetail.value = true;
        try {
            const [editResponse] = await Promise.all([
                fetch(route('project.tasks.edit', { projectEncoded: props.projectId, task: props.task.id }), {
                    headers: { Accept: 'application/json' },
                }),
                fetchParentOptions(),
            ]);
            const body = await editResponse.json();
            const data = body.data;

            if (data) {
                http.title = data.title;
                http.description = data.description ?? '';
                http.status_id = data.status?.id;
                http.priority_id = data.priority?.id;
                http.type_id = data.type?.id;
                http.task_category_id = data.category?.id ?? null;
                http.parent_id = data.parent_id ?? null;
                http.start_date = data.start_date ?? '';
                http.due_date = data.due_date ?? '';
                selectedUserIds.value = (data.users ?? []).map((user: KanbanUser) => user.id);
                originalUserIds.value = [...selectedUserIds.value];
            }

            const taskTags: KanbanBadge[] = data?.tags ?? [];
            selectedTagIds.value = taskTags.map((tag) => tag.id);
            originalTagIds.value = [...selectedTagIds.value];
            existingAttachments.value = data?.media ?? [];
        } catch {
            toast.add({ title: 'Failed', description: 'Could not load task details.', color: 'error' });
        } finally {
            loadingDetail.value = false;
        }
    } else {
        http.title = '';
        http.description = '';
        http.status_id = props.defaultStatusId;
        http.priority_id = undefined;
        http.type_id = undefined;
        http.task_category_id = props.onlyEpicCategory
            ? (categoryOptions.value.find((category) => category.name.toLowerCase() === 'epic')?.id ?? null)
            : props.excludeEpicCategory
                ? (categoryOptions.value.find((category) => category.name.toLowerCase() === 'task')?.id ?? null)
                : null;
        http.parent_id = props.onlyEpicCategory ? null : (props.defaultParentId ?? null);
        http.start_date = '';
        http.due_date = '';
        selectedUserIds.value = currentUserId.value ? [currentUserId.value] : [];
        originalUserIds.value = [];

        loadingDetail.value = true;
        await fetchParentOptions();
        loadingDetail.value = false;
    }
};

const submit = async () => {
    if (props.onlyEpicCategory) http.parent_id = null;

    if (datesRequired.value && !http.due_date) {
        const dueDate = await dueDateDialog.open({
            taskTitle: http.title || 'This task',
            statusName: selectedStatusName.value ?? 'this status',
        });
        if (!dueDate) return;
        http.due_date = dueDate;
    }

    http.assign_users = selectedUserIds.value.filter((id) => !originalUserIds.value.includes(id));
    http.unassign_users = originalUserIds.value.filter((id) => !selectedUserIds.value.includes(id));

    const selectedNewTags = tagStubs.value.filter((tag) => selectedTagIds.value.includes(tag.id));
    const selectedExistingTagIds = selectedTagIds.value.filter((id) => !id.startsWith('new-'));
    http.add_tag = {
        exists: selectedExistingTagIds.filter((id) => !originalTagIds.value.includes(id)),
        new: selectedNewTags.map((tag) => ({ name: tag.name, severity: null })),
    };
    http.remove_tag = originalTagIds.value.filter((id) => !selectedExistingTagIds.includes(id));

    http.attachments = [...existingAttachments.value.map((attachment) => ({ uuid: attachment.uuid })), ...newFiles.value];

    http.start_date = http.start_date || undefined;
    http.due_date = http.due_date || undefined;

    if (isEdit.value && props.task) {
        http.put(route('project.tasks.lazy-update', { projectEncoded: props.projectId, taskEncoded: props.task.id }), {
            onSuccess: () => {
                toast.add({ title: 'Success', description: 'Task updated successfully', color: 'success' });
                emits('close', true);
            },
            onError: () => {
                toast.add({ title: 'Failed', description: 'Could not update task.', color: 'error' });
            },
        });
    } else {
        http.project_id = props.projectId;
        http.sprint_id = props.sprintId ?? undefined;

        http.post(route('project.tasks.lazy-store', { projectEncoded: props.projectId }), {
            onSuccess: () => {
                toast.add({ title: 'Success', description: 'Task created successfully', color: 'success' });
                emits('close', true);
            },
            onError: () => {
                toast.add({ title: 'Failed', description: 'Could not create task.', color: 'error' });
            },
        });
    }
};
</script>

<template>
    <USlideover :title="title" :description="description" class="max-w-2xl w-1/2"
        :close="{ onClick: () => emits('close', false) }" @enter="initialize">
        <template #body>
            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-2">
                        <Label value="Title" required />
                        <UInput v-model="http.title" placeholder="What needs to be done?" size="lg" class="w-full" />
                        <InputError v-if="http.errors.title" :message="http.errors.title" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Description" />
                        <RichTextEditor v-model="http.description as string"
                            placeholder="Add context, acceptance criteria, or links." />
                        <InputError v-if="http.errors.description" :message="http.errors.description" />
                    </div>
                </div>

                <section class="flex flex-col gap-4 border-t border-default pt-6">
                    <h3 class="text-sm font-medium">Classification</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-2">
                            <Label value="Type" required />
                            <USelectMenu v-model="http.type_id" :items="types" label-key="name" value-key="id"
                                placeholder="Select type" class="w-full">
                                <template #item-label="{ item }">
                                    <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{
                                        item.name }}</UBadge>
                                </template>
                            </USelectMenu>
                            <InputError v-if="http.errors.type_id" :message="http.errors.type_id" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label value="Status" required />
                            <USelectMenu v-model="http.status_id" :items="statusOptions" label-key="name" value-key="id"
                                placeholder="Select status" class="w-full">
                                <template #item-label="{ item }">
                                    <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{
                                        item.name }}</UBadge>
                                </template>
                            </USelectMenu>
                            <InputError v-if="http.errors.status_id" :message="http.errors.status_id" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label value="Priority" required />
                            <USelectMenu v-model="http.priority_id" :items="priorities" label-key="name" value-key="id"
                                placeholder="Select priority" class="w-full">
                                <template #item-label="{ item }">
                                    <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{
                                        item.name }}</UBadge>
                                </template>
                            </USelectMenu>
                            <InputError v-if="http.errors.priority_id" :message="http.errors.priority_id" />
                        </div>

                        <div v-if="categoryOptions.length" class="flex flex-col gap-2">
                            <Label value="Category" />
                            <USelectMenu :model-value="http.task_category_id ?? undefined" :items="categoryOptions"
                                label-key="name" value-key="id" placeholder="Select category"
                                :disabled="onlyEpicCategory" clear class="w-full"
                                @update:model-value="(value: string | null | undefined) => (http.task_category_id = value ?? null)"
                                @clear="http.task_category_id = null">
                                <template #item-label="{ item }">
                                    <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{
                                        item.name }}</UBadge>
                                </template>
                            </USelectMenu>
                            <InputError v-if="http.errors.task_category_id" :message="http.errors.task_category_id" />
                        </div>
                    </div>
                </section>

                <section class="flex flex-col gap-4 border-t border-default pt-6">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-sm font-medium">Schedule</h3>
                        <p v-if="scheduleHint" class="text-sm text-warning">{{ scheduleHint }}</p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Start and due date" :required="datesRequired" />

                        <UPopover :open="schedulePickerOpen" @update:open="onSchedulePickerToggle">
                            <UButton color="neutral" variant="subtle" icon="i-lucide-calendar"
                                class="w-full justify-start font-normal sm:w-fit"
                                :class="{ 'text-muted': !http.start_date }" :label="scheduleLabel" />

                            <template #content>
                                <UCalendar v-model="schedule" range :number-of-months="2" class="p-2" />
                            </template>
                        </UPopover>

                        <InputError v-if="http.errors.start_date" :message="http.errors.start_date" />
                        <InputError v-if="http.errors.due_date" :message="http.errors.due_date" />
                    </div>
                </section>

                <section class="flex flex-col gap-4 border-t border-default pt-6">
                    <h3 class="text-sm font-medium">People and context</h3>

                    <div class="flex flex-col gap-2">
                        <Label value="Assignees" />
                        <USelectMenu v-model="selectedUserIds" :items="assignableUsers" label-key="name" value-key="id"
                            multiple placeholder="Select assignees" class="w-full">
                            <template #item-leading="{ item }">
                                <UAvatar :src="item.avatar_url ?? undefined" :alt="item.name" size="xs" />
                            </template>
                        </USelectMenu>
                        <InputError v-if="http.errors.assign_users" :message="http.errors.assign_users" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Tags" />
                        <USelectMenu v-model="selectedTagIds" :items="tagItems" label-key="name" value-key="id" multiple
                            create-item placeholder="Select or create tags" class="w-full" :loading="loadingDetail"
                            @create="onCreateTag">
                            <template #item-label="{ item }">
                                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}
                                </UBadge>
                            </template>
                        </USelectMenu>
                    </div>

                    <div v-if="!onlyEpicCategory" class="flex flex-col gap-2">
                        <Label value="Parent Task" />
                        <USelectMenu :model-value="http.parent_id ?? undefined" :items="availableParentOptions"
                            label-key="title" value-key="id" placeholder="No parent (top-level task)"
                            :loading="loadingDetail" clear class="w-full"
                            @update:model-value="(value: string | null | undefined) => (http.parent_id = value ?? null)"
                            @clear="http.parent_id = null">
                            <template #item-label="{ item }">
                                <div class="flex items-center gap-2">
                                    <span class="truncate">{{ item.title }}</span>
                                    <UBadge v-if="item.category" color="neutral" variant="subtle" size="sm">{{
                                        item.category.name }}</UBadge>
                                </div>
                            </template>
                        </USelectMenu>
                        <InputError v-if="http.errors.parent_id" :message="http.errors.parent_id" />
                    </div>
                </section>

                <section class="flex flex-col gap-4 border-t border-default pt-6">
                    <h3 class="text-sm font-medium">Attachments</h3>

                    <ul v-if="existingAttachments.length"
                        class="divide-y divide-default rounded-lg border border-default">
                        <li v-for="attachment in existingAttachments" :key="attachment.uuid"
                            class="flex items-center gap-3 p-3">
                            <UIcon name="i-lucide-paperclip" class="size-4 shrink-0 text-muted" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm">{{ attachment.file_name }}</p>
                                <p class="text-xs text-muted tabular-nums">{{ formatFileSize(attachment.size) }}</p>
                            </div>
                            <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="xs"
                                :aria-label="`Remove ${attachment.file_name}`"
                                @click="removeExistingAttachment(attachment.uuid)" />
                        </li>
                    </ul>

                    <UFileUpload v-model="newFiles" multiple label="Drop files here or click to browse"
                        description="Up to 20 MB per file" />
                    <InputError v-if="http.errors.attachments" :message="http.errors.attachments" />
                </section>
            </div>
        </template>

        <template #footer>
            <div class="flex w-full items-center justify-end gap-2">
                <UButton label="Cancel" color="neutral" variant="ghost" :disabled="http.processing"
                    @click="emits('close', false)" />
                <UButton :label="isEdit ? 'Save Changes' : 'Create Task'" :loading="http.processing"
                    :disabled="http.processing" @click="submit" />
            </div>
        </template>
    </USlideover>
</template>
