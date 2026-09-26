<script setup lang="ts">
import TaskFormFields from '@/components/task/TaskFormFields.vue';
import type { TaskFormModel } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { DialogTitle } from 'reka-ui';
import { onMounted, ref } from 'vue';
import type { ShellProject, TaskEditSeed, TaskOption, TaskOptionUser } from './types';
import { useTaskDetailData } from './useTaskDetailData';

interface ParentOption {
    id: string;
    parent_id: string | null;
    title: string;
}

const props = defineProps<{
    projectId: string;
    project: ShellProject;
    task: TaskEditSeed;
    priorities: TaskOption[];
    statuses: TaskOption[];
    types: TaskOption[];
    categories: TaskOption[];
    tags: TaskOption[];
    assignableUsers: TaskOptionUser[];
}>();

const emit = defineEmits<{ close: [boolean] }>();

const { failed: detailFailed, detail, load: loadDetail } = useTaskDetailData(
    () => props.projectId,
    () => props.task.id,
);

const loading = ref(true);
const loadFailed = ref(false);
const parentOptions = ref<ParentOption[]>([]);

/** Nilai awal dari server, dipakai menghitung selisih assignee dan tag saat submit. */
const originalUserIds = ref<string[]>([]);
const originalTagIds = ref<string[]>([]);

const form = useForm({
    model: {
        title: props.task.title,
        description: '',
        parent_id: props.task.parent_id ?? undefined,
        task_category_id: props.task.category?.id ?? undefined,
        type_id: undefined,
        status_id: props.task.status?.id ?? undefined,
        priority_id: props.task.priority?.id ?? undefined,
        start_date: '',
        due_date: '',
        assign_users: [],
        tags: [],
        newTags: [],
        attachments: [],
    } as TaskFormModel,
    is_archived: false,
});

const getJson = async (url: string) => {
    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });

    if (!response.ok) {
        throw new Error('request failed');
    }

    return (await response.json()).data ?? null;
};

/**
 * `BacklogTaskData` sengaja ramping — description, type, tanggal, tags dan media tidak ikut
 * supaya payload backlog tidak membengkak. Isi lengkapnya diambil saat drawer dibuka. Ini
 * wajib: endpoint update menerima daftar lampiran secara utuh, jadi mengirim form tanpa
 * memuat lampiran yang sudah ada akan menghapusnya.
 */
const applyDetail = () => {
    if (!detail.value) return;

    originalUserIds.value = detail.value.users.map((user) => user.id);
    originalTagIds.value = detail.value.tags.map((tag) => tag.id);

    form.model = {
        title: detail.value.title,
        description: detail.value.description ?? '',
        parent_id: detail.value.parent_id ?? undefined,
        task_category_id: detail.value.category?.id ?? undefined,
        type_id: detail.value.type_id ?? undefined,
        status_id: detail.value.status_id ?? undefined,
        priority_id: detail.value.priority_id ?? undefined,
        start_date: detail.value.start_date ?? '',
        due_date: detail.value.due_date ?? '',
        assign_users: [...originalUserIds.value],
        tags: [...originalTagIds.value],
        newTags: [],
        attachments: detail.value.media,
    };

    form.is_archived = detail.value.is_archived ?? false;
};

const load = async () => {
    loading.value = true;
    loadFailed.value = false;

    try {
        const [, parents] = await Promise.all([
            loadDetail(),
            getJson(route('project.tasks.parent-options', { projectEncoded: props.projectId })),
        ]);

        if (detailFailed.value) {
            throw new Error('incomplete task detail');
        }

        applyDetail();
        parentOptions.value = (parents ?? []).filter((option: ParentOption) => option.id !== props.task.id);
    } catch {
        loadFailed.value = true;
    } finally {
        loading.value = false;
    }
};

onMounted(load);

/**
 * Endpoint update memakai selisih (assign/unassign, add/remove) dan nama field yang datar,
 * sementara form ini menyimpan daftar terpilih utuh di dalam satu objek model.
 * Penerjemahannya dilakukan di sini, tepat sebelum kirim.
 */
const submit = () => {
    form
        .transform((data) => ({
            _method: 'PUT',
            title: data.model.title.trim(),
            description: data.model.description,
            parent_id: data.model.parent_id ?? null,
            task_category_id: data.model.task_category_id ?? null,
            type_id: data.model.type_id ?? null,
            status_id: data.model.status_id ?? null,
            priority_id: data.model.priority_id ?? null,
            start_date: data.model.start_date || null,
            due_date: data.model.due_date || null,
            is_archived: data.is_archived,
            assign_users: data.model.assign_users.filter((id) => !originalUserIds.value.includes(id)),
            unassign_users: originalUserIds.value.filter((id) => !data.model.assign_users.includes(id)),
            add_tag: {
                exists: data.model.tags.filter((id) => !originalTagIds.value.includes(id)),
                new: data.model.newTags,
            },
            remove_tag: originalTagIds.value.filter((id) => !data.model.tags.includes(id)),
            attachments: data.model.attachments,
        }))
        .post(route('project.tasks.update', { projectEncoded: props.projectId, taskEncoded: props.task.id }), {
            preserveScroll: true,
            onSuccess: () => emit('close', true),
        });
};
</script>

<template>
    <USlideover title="Edit task" class="w-full max-w-xl" @update:open="(open: boolean) => !open && emit('close', false)">
        <template #header>
            <div class="flex min-w-0 items-start gap-3">
                <span class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                    <UIcon name="i-lucide-pencil" class="size-5" />
                </span>

                <div class="flex min-w-0 flex-col gap-1">
                    <DialogTitle as="h2" class="text-highlighted text-xl leading-tight font-bold">Edit task</DialogTitle>

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
                    :disabled="form.processing"
                    @click="emit('close', false)"
                />
            </div>
        </template>

        <template #body>
            <form id="task-edit" class="flex flex-col gap-5" @submit.prevent="submit">
                <UAlert
                    v-if="loadFailed"
                    color="error"
                    variant="soft"
                    description="Could not load task details."
                    :actions="[{ label: 'Retry', color: 'neutral', variant: 'subtle', onClick: load }]"
                />

                <USkeleton v-if="loading" class="h-96 w-full rounded-lg" />

                <TaskFormFields
                    v-else
                    v-model="form.model"
                    :statuses="statuses"
                    :priorities="priorities"
                    :types="types"
                    :categories="categories"
                    :tags="tags"
                    :assignable-users="assignableUsers"
                    :parents="parentOptions"
                    :errors="form.errors"
                    :disabled="form.processing"
                >
                    <template #extra>
                        <UFormField
                            name="is_archived"
                            orientation="horizontal"
                            description="Hide from boards and filters. You can restore it anytime."
                            :error="form.errors.is_archived"
                            :ui="{ root: 'flex items-start justify-between gap-4', description: 'text-xs' }"
                        >
                            <template #label>
                                <span class="text-sm font-semibold">Archive task</span>
                            </template>

                            <USwitch v-model="form.is_archived" :disabled="form.processing" />
                        </UFormField>
                    </template>
                </TaskFormFields>
            </form>
        </template>

        <template #footer>
            <div class="flex w-full justify-end gap-2">
                <UButton label="Cancel" color="neutral" variant="ghost" :disabled="form.processing" @click="emit('close', false)" />
                <UButton
                    label="Update task"
                    icon="i-lucide-save"
                    type="submit"
                    form="task-edit"
                    :loading="form.processing"
                    :disabled="form.processing || loading || loadFailed"
                />
            </div>
        </template>
    </USlideover>
</template>
