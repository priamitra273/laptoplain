<script setup lang="ts">
import DatePicker from '@/components/form/DatePicker.vue';
import BadgeSelect from '@/components/form/BadgeSelect.vue';
import { formatDate } from '@/lib/date';
import { FetchJsonError, fetchJson } from '@/lib/utils';
import { usePage } from '@inertiajs/vue3';
import { parseDate } from '@internationalized/date';
import { computed, reactive, ref } from 'vue';
import { statusRequiresDueDate } from '@/lib/statusRules';
import type { KanbanBadge, KanbanStatus, KanbanTask, KanbanUser, QuickAddDraft } from './types';

const props = defineProps<{
    projectId: string;
    status: KanbanStatus;
    types: KanbanBadge[];
    priorities: KanbanBadge[];
    assignableUsers: KanbanUser[];

    /** Terisi hanya saat form dibuka lewat tombol subtask di sebuah kartu. */
    parentTask?: KanbanTask | null;
}>();

const emit = defineEmits<{
    created: [];
    cancel: [];
    openFull: [draft: QuickAddDraft];
}>();

const requiresDates = computed(() => statusRequiresDueDate(props.status.name));

/**
 * Task yang dibuat lewat form ini biasanya untuk diri sendiri, jadi pembuatnya sudah terpilih
 * sejak awal — tapi tetap bisa dihapus, ini cuma nilai awal `assign_users`, bukan yang dikunci.
 * Kalau pembuatnya tidak ada di daftar assignable (di luar tim project, dsb.), dilewati saja.
 */
const page = usePage();
const currentUserId = computed(() => (page.props.auth as { user: { id: string } | null }).user?.id ?? null);

const defaultAssignUsers = (): string[] => {
    const id = currentUserId.value;

    return id && props.assignableUsers.some((user) => user.id === id) ? [id] : [];
};

const form = reactive({
    title: '',
    type_id: undefined as string | undefined,
    priority_id: undefined as string | undefined,
    assign_users: defaultAssignUsers(),
    start_date: '',
    due_date: '',
});

const errors = ref<Record<string, string[]>>({});
const failureMessage = ref('');
const processing = ref(false);

const userOptions = computed(() => props.assignableUsers.map((user) => ({ id: user.id, label: user.name })));

/** Cermin `startDate`/`dueDate` di TaskEditDrawer: DatePicker memakai CalendarDate, form menyimpan string polos. */
const startDate = computed({
    get: () => (form.start_date ? parseDate(form.start_date) : undefined),
    set: (value) => {
        form.start_date = value ? value.toString() : '';
    },
});

const dueDate = computed({
    get: () => (form.due_date ? parseDate(form.due_date) : undefined),
    set: (value) => {
        form.due_date = value ? value.toString() : '';
    },
});

const submit = async () => {
    processing.value = true;
    errors.value = {};
    failureMessage.value = '';

    try {
        await fetchJson(route('project.tasks.lazy-store', { projectEncoded: props.projectId }), 'POST', {
            project_id: props.projectId,
            parent_id: props.parentTask?.id ?? null,
            status_id: props.status.id,
            type_id: form.type_id ?? null,
            priority_id: form.priority_id ?? null,
            title: form.title,
            assign_users: form.assign_users,
            start_date: form.start_date || null,
            due_date: form.due_date || null,
        });

        useToast().add({ title: 'Success', description: 'Task created.', color: 'success' });

        emit('created');
    } catch (error) {
        if (error instanceof FetchJsonError && error.status === 422) {
            errors.value = (error.data as { errors?: Record<string, string[]> })?.errors ?? {};
        }

        failureMessage.value = error instanceof Error ? error.message : 'Could not create the task.';
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="flex flex-col gap-2 rounded-lg border border-default bg-default p-2.5 shadow-sm transition-colors focus-within:border-primary">
        <div v-if="parentTask" class="flex min-w-0 items-center gap-1.5 text-xs">
            <UIcon name="i-lucide-corner-down-right" class="size-3.5 shrink-0 text-muted" />
            <span class="shrink-0 text-muted">Subtask of</span>
            <span class="min-w-0 truncate font-medium text-highlighted">{{ parentTask.title }}</span>
        </div>

        <UAlert v-if="failureMessage" color="error" variant="soft" :description="failureMessage" />

        <UFormField name="title" :error="errors.title?.[0]">
            <UTextarea
                v-model="form.title"
                placeholder="Task title…"
                :rows="2"
                autoresize
                autofocus
                aria-label="Task title"
                class="w-full"
                @keydown.escape="emit('cancel')"
            />
        </UFormField>

        <UFormField name="type_id" :error="errors.type_id?.[0]">
            <BadgeSelect v-model="form.type_id" :items="types" placeholder="Type" class="w-full" />
        </UFormField>

        <UFormField name="priority_id" :error="errors.priority_id?.[0]">
            <BadgeSelect display="priority" v-model="form.priority_id" :items="priorities" placeholder="Priority" class="w-full" />
        </UFormField>

        <UFormField name="assign_users" :error="errors.assign_users?.[0]">
            <USelectMenu
                v-model="form.assign_users"
                :items="userOptions"
                label-key="label"
                value-key="id"
                multiple
                placeholder="Assign to"
                class="w-full"
            />
        </UFormField>

        <div class="flex flex-col gap-2">
            <UFormField name="start_date" :error="errors.start_date?.[0]">
                <DatePicker
                    v-model="startDate"
                    :label="startDate ? formatDate(startDate.toString()) : 'Start date'"
                    trigger-aria-label="Select start date"
                    trigger-class="w-full"
                    clearable
                />
            </UFormField>

            <UFormField name="due_date" :error="errors.due_date?.[0]">
                <DatePicker
                    v-model="dueDate"
                    :label="dueDate ? formatDate(dueDate.toString()) : 'Due date'"
                    trigger-aria-label="Select due date"
                    :min-value="startDate"
                    trigger-class="w-full"
                    clearable
                />
            </UFormField>
        </div>

        <p v-if="requiresDates" class="text-xs text-dimmed">Dates are required for the {{ status.name }} status.</p>

        <div class="flex items-center gap-1 border-t border-default pt-2">
            <UButton label="Create" size="sm" :loading="processing" :disabled="processing" @click="submit" />
            <UButton label="Cancel" size="sm" color="neutral" variant="ghost" :disabled="processing" @click="emit('cancel')" />

            <!-- Untuk field yang tidak ditawarkan form ringkas ini: description, category, tags, lampiran. -->
            <UButton
                icon="i-lucide-sliders-horizontal"
                size="sm"
                color="neutral"
                variant="ghost"
                class="ms-auto"
                aria-label="More options"
                :disabled="processing"
                @click="
                    emit('openFull', {
                        title: form.title,
                        type_id: form.type_id,
                        priority_id: form.priority_id,
                        assign_users: form.assign_users,
                        start_date: form.start_date,
                        due_date: form.due_date,
                    })
                "
            />
        </div>
    </div>
</template>
