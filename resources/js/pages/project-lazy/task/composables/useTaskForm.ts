import { useProjectPermissions } from '@/composables/useProjectPermissions';
import type { LazyTaskFormData, LazyTaskFormProps, ParentTaskNode, SlimUser, TagOption, TaskCategoryOption } from '@/pages/project-lazy';
import type { UploadedFile } from '@/types';
import { ProjectPolicyKey } from '@/types/type';
import { useForm, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { useToast } from 'primevue/usetoast';
import { computed, inject, ref, watch } from 'vue';

const findParentTaskById = (tasks: ParentTaskNode[], id: string): ParentTaskNode | null => {
    for (const task of tasks) {
        if (task.id === id) {
            return task;
        }
        const children = task.sub_task_recursive ?? [];
        if (children.length > 0) {
            const found = findParentTaskById(children, id);
            if (found) {
                return found;
            }
        }
    }
    return null;
};

interface TaskFormEmit {
    (e: 'close'): void;
    (e: 'saved'): void;
}

export const useTaskForm = (props: LazyTaskFormProps, emit: TaskFormEmit) => {
    const toast = useToast();

    const policy = inject(ProjectPolicyKey, null);
    const { canUpdateTaskField, canUpdateTaskStatus } = useProjectPermissions(policy);

    const form = useForm<LazyTaskFormData>({
        _method: props?.task ? 'PUT' : 'POST',
        project_id: props.projectId,
        title: props?.task?.title ?? '',
        description: props?.task?.description ?? '',
        type_id: props?.task?.type?.id != null ? String(props.task.type.id) : null,
        status_id: props?.task?.status?.id != null ? String(props.task.status.id) : null,
        priority_id: props?.task?.priority?.id != null ? String(props.task.priority.id) : null,
        task_category_id: props?.task?.category?.id ?? (props.excludeEpicCategory ? null : null),
        sprint_id: props.sprintId ?? null,
        parent_id: props.onlyEpicCategory ? null : (props?.parentId ?? null),
        start_date: props?.task?.start_date ? new Date(props.task.start_date) : null,
        due_date: props?.task?.due_date ? new Date(props.task.due_date) : null,
        is_archived: props?.task?.is_archived ?? false,
        progress_value: props?.task?.progress ?? 0,
        assign_users: [],
        unassign_users: [],
        add_tag: {
            new: [],
            exists: [],
        },
        attachments: (props.task?.media as UploadedFile[] | undefined) ?? [],
        remove_tag: [],
    });

    const selectedMembers = ref<SlimUser[]>([]);
    const selectedTags = ref<TagOption[]>([]);
    const validationErrors = ref<Record<string, string>>({});

    const authUser = computed(() => usePage().props.auth.user);

    const fieldDisabled = (field: string): boolean => !canUpdateTaskField(field);

    const formattedMemberOption = computed<SlimUser[]>(() => props.members.map((m) => m.user));

    const statusOption = computed(() =>
        props.taskStatuses.filter((s) => {
            return canUpdateTaskStatus(s.id) || props.task?.status_id === s.id;
        }),
    );

    const existedMembers = computed<SlimUser[]>(() =>
        (props.task?.users ?? []).map((u) => ({ id: String(u.id), name: u.name, email: u.email, avatar_url: u.avatar_url })),
    );

    const selectedParentId = computed({
        get: () => {
            if (!form.parent_id) {
                return null;
            }
            const value: Record<string, boolean> = {};
            value[form.parent_id] = true;
            return value;
        },
        set: (val) => {
            form.parent_id = val ? Object.keys(val)[0] : null;
        },
    });

    const isEpicParentContext = computed(() => {
        if (!props.parentId) {
            return false;
        }
        const parent = findParentTaskById(props.tasks, props.parentId);
        return (parent?.category?.name ?? '').toLowerCase() === 'epic';
    });

    const categoryOptions = computed<TaskCategoryOption[]>(() => {
        if (props.onlyEpicCategory) {
            return (props.taskCategories ?? []).filter((category) => category.name?.toLowerCase() === 'epic');
        }
        if (!props.excludeEpicCategory && !isEpicParentContext.value) {
            return props.taskCategories ?? [];
        }
        return (props.taskCategories ?? []).filter((category) => category.name?.toLowerCase() !== 'epic');
    });

    const defaultBacklogCategoryId = computed<string | null>(() => {
        const taskCategory = categoryOptions.value.find((category) => category.name?.toLowerCase() === 'task');
        return taskCategory?.id ?? null;
    });

    const defaultEpicCategoryId = computed<string | null>(() => {
        const epicCategory = (props.taskCategories ?? []).find((category) => category.name?.toLowerCase() === 'epic');
        return epicCategory?.id ?? null;
    });

    const tagOptions = computed<TagOption[]>(() => props.tags);

    const minDueDate = computed(() => (form.start_date ? form.start_date : undefined));

    const isInProgressStatus = computed(() => {
        const status = props.taskStatuses.find((s) => s.id === form.status_id);
        return status?.name === 'In Progress';
    });

    const normalizeStatusName = (statusName: string): string => statusName.replace(/\s+/g, '').toLowerCase();

    const requiresDates = computed(() => {
        const status = props.taskStatuses.find((s) => s.id === form.status_id);
        return !!status && !['todo', 'blocked'].includes(normalizeStatusName(status.name));
    });

    const isEdit = computed(() => !!props.task);

    const onStatusChange = (newStatusId: string | null): void => {
        delete validationErrors.value.due_date;

        if (!newStatusId) {
            form.progress_value = 0;
            return;
        }

        const status = props.taskStatuses.find((s) => s.id === newStatusId);
        form.progress_value = status?.score ?? 0;

        if (status?.name && normalizeStatusName(status.name) === 'todo') {
            form.due_date = null;
        }
    };

    const submit = (): void => {
        if (props.onlyEpicCategory) {
            form.parent_id = null;
        }

        const routeName = isEdit.value ? 'project.tasks.update' : 'project.tasks.store';

        const existed = existedMembers.value.map((u) => u.id);
        const selected = selectedMembers.value.map((u) => u.id);

        form.assign_users = selected.filter((id) => !existed.includes(id));
        form.unassign_users = existed.filter((id) => !selected.includes(id));

        const oldTags = props?.task?.tags?.map((t) => String(t.id)) ?? [];
        const tagExist = selectedTags.value.filter((t) => t.id);
        const tagExistIds = tagExist.map((t) => t.id);
        const addTagExist = tagExistIds.filter((id) => !oldTags.includes(id));
        const addTagNew = selectedTags.value.filter((t) => !t.id);
        const removeTags = oldTags.filter((id) => !tagExistIds.includes(id));

        form.add_tag.new = addTagNew;
        form.add_tag.exists = addTagExist;
        form.remove_tag = removeTags;

        const param: Record<string, string | undefined> = { projectEncoded: props.projectId };

        if (isEdit.value) {
            param.taskEncoded = props.task?.id;
        }

        form.transform(function (data) {
            return {
                ...data,
                start_date: data.start_date ? moment(data.start_date).format('YYYY-MM-DD') : null,
                due_date: data.due_date ? moment(data.due_date).format('YYYY-MM-DD') : null,
            };
        }).post(route(routeName, param), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
                form.reset();
                validationErrors.value = {};
            },
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: isEdit.value ? 'Failed to update task' : 'Failed to store task',
                    life: 3000,
                });
            },
        });
    };

    watch(
        existedMembers,
        (val) => {
            const members = [...val];

            if (!authUser.value) {
                selectedMembers.value = members;
                return;
            }

            const authExistsInOptions = formattedMemberOption.value.some((m) => m.id === authUser.value.id);
            const authExistsInMembers = members.some((m) => m.id === authUser.value.id);

            if (authExistsInOptions && !authExistsInMembers) {
                members.push({ id: authUser.value.id, name: authUser.value.name, avatar_url: authUser.value.avatar_url, email: authUser.value.email });
            }

            selectedMembers.value = members;
        },
        { immediate: true },
    );

    watch(
        () => props.task?.tags,
        (tags) => {
            if (tags && Array.isArray(tags)) {
                selectedTags.value = tags.map((t) => ({
                    id: String(t.id),
                    name: t.name,
                    severity: t.severity ?? '',
                }));
            }
        },
        { immediate: true },
    );

    watch(
        categoryOptions,
        (options) => {
            if (form.task_category_id && !options.some((category) => category.id === form.task_category_id)) {
                form.task_category_id = null;
            }

            if (!props.task && props.excludeEpicCategory && !form.task_category_id) {
                form.task_category_id = defaultBacklogCategoryId.value;
            }
            if (!props.task && props.onlyEpicCategory && !form.task_category_id) {
                form.task_category_id = defaultEpicCategoryId.value;
            }
        },
        { immediate: true },
    );

    return {
        form,
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
    };
};
