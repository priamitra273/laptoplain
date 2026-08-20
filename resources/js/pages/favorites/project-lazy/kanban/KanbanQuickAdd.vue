<script setup lang="ts">
import { severityColor } from '@/lib/utils';
import { useHttp, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { KanbanBadge, KanbanStatusOption, KanbanUser } from './types';

interface Props {
    status: KanbanStatusOption;
    projectId: string;
    sprintId?: string | null;
    priorities?: KanbanBadge[];
    types?: KanbanBadge[];
    assignableUsers?: KanbanUser[];
}

const props = withDefaults(defineProps<Props>(), {
    sprintId: null,
    priorities: () => [],
    types: () => [],
    assignableUsers: () => [],
});

const emits = defineEmits<{
    cancel: [];
    created: [];
    openFull: [];
}>();

const toast = useToast();
const page = usePage();
const currentUserId = computed(() => (page.props.auth as { user: { id: string } }).user.id);

const requiresDueDate = computed(() => !['To Do', 'Blocked'].includes(props.status.name));

interface QuickAddFormData {
    title: string;
    status_id: string;
    priority_id?: string;
    type_id?: string;
    project_id: string;
    sprint_id?: string;
    assign_users: string[];
    start_date?: string;
    due_date?: string;
    [key: string]: any;
}

const http = useHttp<QuickAddFormData>({
    title: '',
    status_id: props.status.id,
    priority_id: undefined,
    type_id: undefined,
    project_id: props.projectId,
    sprint_id: props.sprintId ?? undefined,
    assign_users: currentUserId.value ? [currentUserId.value] : [],
    start_date: '',
    due_date: '',
});

const submit = () => {
    http.start_date = http.start_date || undefined;
    http.due_date = http.due_date || undefined;

    http.post(route('project.tasks.lazy-store', { projectEncoded: props.projectId }), {
        onSuccess: () => {
            toast.add({ title: 'Success', description: 'Task created successfully', color: 'success' });
            emits('created');
        },
        onError: () => {
            toast.add({ title: 'Failed', description: 'Could not create task.', color: 'error' });
        },
    });
};
</script>

<template>
    <div class="mb-2 flex flex-col gap-2 rounded-lg border border-primary bg-default p-2.5 shadow-sm">
        <div>
            <UTextarea
                v-model="http.title"
                placeholder="Task title…"
                :rows="2"
                autoresize
                autofocus
                class="w-full"
                @keydown.escape="emits('cancel')"
            />
            <InputError v-if="http.errors.title" :message="http.errors.title" />
        </div>

        <div>
            <USelectMenu v-model="http.type_id" :items="types" label-key="name" value-key="id" placeholder="Type *" class="w-full">
                <template #item-label="{ item }">
                    <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                </template>
            </USelectMenu>
            <InputError v-if="http.errors.type_id" :message="http.errors.type_id" />
        </div>

        <div>
            <USelectMenu v-model="http.priority_id" :items="priorities" label-key="name" value-key="id" placeholder="Priority *" class="w-full">
                <template #item-label="{ item }">
                    <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                </template>
            </USelectMenu>
            <InputError v-if="http.errors.priority_id" :message="http.errors.priority_id" />
        </div>

        <div>
            <USelectMenu
                v-model="http.assign_users"
                :items="assignableUsers"
                label-key="name"
                value-key="id"
                multiple
                placeholder="Assign to"
                class="w-full"
            >
                <template #item-leading="{ item }">
                    <UAvatar :src="item.avatar_url ?? undefined" :alt="item.name" size="xs" />
                </template>
            </USelectMenu>
            <InputError v-if="http.errors.assign_users" :message="http.errors.assign_users" />
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div>
                <UInput v-model="http.start_date as string" type="date" placeholder="Start date" class="w-full" />
                <InputError v-if="http.errors.start_date" :message="http.errors.start_date" />
            </div>
            <div>
                <UInput
                    v-model="http.due_date as string"
                    type="date"
                    :min="http.start_date"
                    :placeholder="requiresDueDate ? 'Due date *' : 'Due date'"
                    class="w-full"
                />
                <InputError v-if="http.errors.due_date" :message="http.errors.due_date" />
            </div>
        </div>

        <div class="flex items-center gap-1 border-t border-default pt-2">
            <UButton label="Create" size="sm" :loading="http.processing" :disabled="http.processing" @click="submit" />
            <UButton label="Cancel" size="sm" color="neutral" variant="ghost" @click="emits('cancel')" />
            <UButton
                icon="i-lucide-external-link"
                size="sm"
                color="neutral"
                variant="ghost"
                class="ml-auto"
                title="Open full form"
                @click="emits('openFull')"
            />
        </div>
    </div>
</template>
