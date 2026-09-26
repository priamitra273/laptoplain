<script setup lang="ts">
import TaskFormFields from '@/components/task/TaskFormFields.vue';
import type { TaskFormModel } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { DialogTitle } from 'reka-ui';
import { onScopeDispose, ref } from 'vue';
import type { ShellProject, TaskOption, TaskOptionUser } from '../types';
import type { ListTask } from './types';

const props = defineProps<{
    projectId: string;
    project: ShellProject;
    parent: ListTask | null;
    parents: { id: string; title: string; parent_id?: string | null }[];
    statuses: TaskOption[];
    priorities: TaskOption[];
    types: TaskOption[];
    categories: TaskOption[];
    tags: TaskOption[];
    assignableUsers: TaskOptionUser[];
    /** Nilai awal dari kartu quick-add Kanban, supaya isian yang sudah diketik tidak hilang saat pindah ke form lengkap. */
    initial?: {
        title?: string;
        status_id?: string;
        priority_id?: string;
        type_id?: string;
        assign_users?: string[];
        start_date?: string;
        due_date?: string;
    };
}>();

const emit = defineEmits<{ close: [boolean] }>();

const page = usePage();
const currentUserId = String(page.props.auth.user.id);
const defaultAssignees = props.assignableUsers.some((user) => user.id === currentUserId) ? [currentUserId] : [];

const form = ref<TaskFormModel>({
    title: props.initial?.title ?? '',
    description: '',
    parent_id: props.parent?.id,
    status_id: props.initial?.status_id,
    priority_id: props.initial?.priority_id,
    type_id: props.initial?.type_id,
    task_category_id: undefined,
    start_date: props.initial?.start_date ?? '',
    due_date: props.initial?.due_date ?? '',
    assign_users: props.initial?.assign_users?.length ? props.initial.assign_users : defaultAssignees,
    tags: [],
    newTags: [],
    attachments: [],
});

const errors = ref<Record<string, string[]>>({});
const failure = ref('');
const processing = ref(false);
let controller: AbortController | null = null;
onScopeDispose(() => controller?.abort());

const submit = async () => {
    if (processing.value) return;

    processing.value = true;
    failure.value = '';
    errors.value = {};
    controller = new AbortController();

    const model = form.value;
    const body = new FormData();
    body.append('project_id', props.projectId);

    for (const key of [
        'title',
        'description',
        'parent_id',
        'status_id',
        'priority_id',
        'type_id',
        'task_category_id',
        'start_date',
        'due_date',
    ] as const) {
        body.append(key, key === 'title' ? model.title.trim() : (model[key] ?? ''));
    }

    body.append('is_archived', '0');
    model.assign_users.forEach((id, index) => body.append(`assign_users[${index}]`, id));
    model.tags.forEach((id, index) => body.append(`add_tag[exists][${index}]`, id));
    model.newTags.forEach((tag, index) => {
        body.append(`add_tag[new][${index}][name]`, tag.name);
        body.append(`add_tag[new][${index}][severity]`, tag.severity);
    });
    model.attachments.forEach((file, index) => {
        if (file instanceof File) body.append(`attachments[${index}]`, file);
    });

    try {
        const csrf = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '';
        const response = await fetch(route('project.tasks.lazy-store', { projectEncoded: props.projectId }), {
            method: 'POST',
            body,
            signal: controller.signal,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': decodeURIComponent(csrf) },
        });
        const data = await response.json().catch(() => null);

        if (!response.ok || data?.success !== true) {
            errors.value = data?.errors ?? {};
            throw new Error(data?.message ?? 'Could not create the task. Please try again.');
        }

        emit('close', true);
    } catch (error) {
        if (!controller.signal.aborted) failure.value = error instanceof Error ? error.message : 'Could not create the task.';
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <USlideover
        :title="parent ? 'Create subtask' : 'Create task'"
        class="w-full max-w-xl"
        :dismissible="!processing"
        @update:open="(open: boolean) => !open && !processing && emit('close', false)"
    >
        <template #header>
            <div class="flex min-w-0 items-start gap-3">
                <span class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                    <UIcon name="i-lucide-plus" class="size-5" />
                </span>

                <div class="flex min-w-0 flex-col gap-1">
                    <DialogTitle as="h2" class="text-highlighted text-xl leading-tight font-bold">
                        {{ parent ? 'Create subtask' : 'Create task' }}
                    </DialogTitle>

                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        <span class="text-muted shrink-0 text-sm">in</span>
                        <span class="text-default min-w-0 truncate text-sm font-medium">{{ project.title }}</span>
                        <span v-if="project.project_no" class="text-dimmed shrink-0 font-mono text-xs">{{ project.project_no }}</span>
                    </div>
                </div>

                <UButton
                    icon="i-lucide-x"
                    color="neutral"
                    variant="ghost"
                    size="sm"
                    square
                    aria-label="Close"
                    class="ms-auto shrink-0"
                    :disabled="processing"
                    @click="emit('close', false)"
                />
            </div>
        </template>

        <template #body>
            <form id="list-task-create" class="flex flex-col gap-5" @submit.prevent="submit">
                <UAlert v-if="failure" color="error" variant="soft" :description="failure" />

                <TaskFormFields
                    v-model="form"
                    :statuses="statuses"
                    :priorities="priorities"
                    :types="types"
                    :categories="categories"
                    :tags="tags"
                    :assignable-users="assignableUsers"
                    :parents="parents"
                    :errors="errors"
                    :disabled="processing"
                />
            </form>
        </template>

        <template #footer>
            <div class="flex w-full justify-end gap-2">
                <UButton label="Cancel" color="neutral" variant="ghost" :disabled="processing" @click="emit('close', false)" />
                <UButton label="Create task" type="submit" form="list-task-create" :loading="processing" :disabled="processing" />
            </div>
        </template>
    </USlideover>
</template>
