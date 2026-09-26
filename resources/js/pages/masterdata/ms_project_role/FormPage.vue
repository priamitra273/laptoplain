<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { severityColor } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import { Head, Link, useForm, type InertiaForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed } from 'vue';

interface TaskStatusOption {
    id: string;
    name: string;
    severity: PrimeSeverity;
}

interface ProjectRoleConfig {
    task?: string[];
    project_member?: string[];
    sprint?: string[];
    allow_task_status?: string[];
    allow_update_task_fields?: string[];
}

interface ProjectRoleRecord {
    id: string;
    name: string;
    config?: ProjectRoleConfig | null;
}

interface Props {
    project_role?: ProjectRoleRecord;
    task_statuses?: TaskStatusOption[];
}

const props = withDefaults(defineProps<Props>(), {
    task_statuses: () => [],
});

const isEdit = computed(() => !!props.project_role?.id);
const pageTitle = computed(() => (isEdit.value ? 'Edit Project Role' : 'Add Project Role'));

type PermissionSectionKey = 'task' | 'project_member' | 'sprint';

const PERMISSION_SECTIONS: { key: PermissionSectionKey; label: string }[] = [
    { key: 'task', label: 'Task' },
    { key: 'project_member', label: 'Project Member' },
    { key: 'sprint', label: 'Sprint' },
];

const ACTIONS = ['create', 'update', 'delete'] as const;
const actionItems: string[] = [...ACTIONS];
const accessControlFieldUi = { root: 'grid gap-4 md:grid-cols-3', container: 'mt-0 md:col-span-2' };

const TASK_FIELDS = [
    { value: 'title', label: 'Title' },
    { value: 'description', label: 'Description' },
    { value: 'status', label: 'Status' },
    { value: 'priority', label: 'Priority' },
    { value: 'type', label: 'Type' },
    { value: 'category', label: 'Category' },
    { value: 'start_date', label: 'Start Date' },
    { value: 'end_date', label: 'End Date' },
    { value: 'due_date', label: 'Due Date' },
    { value: 'tags', label: 'Tags' },
    { value: 'assignee', label: 'Assignee' },
    { value: 'parent', label: 'Parent' },
];

interface ProjectRoleFormData {
    _method: string;
    name: string;
    config: {
        task: string[];
        project_member: string[];
        sprint: string[];
        allow_task_status: string[];
        allow_update_task_fields: string[];
    };
    [key: string]: any;
}

const form: InertiaForm<ProjectRoleFormData> = useForm({
    _method: props.project_role?.id ? 'PUT' : 'POST',
    name: props.project_role?.name ?? '',
    config: {
        task: [...(props.project_role?.config?.task ?? [])],
        project_member: [...(props.project_role?.config?.project_member ?? [])],
        sprint: [...(props.project_role?.config?.sprint ?? [])],
        allow_task_status: [...(props.project_role?.config?.allow_task_status ?? [])],
        allow_update_task_fields: [...(props.project_role?.config?.allow_update_task_fields ?? [])],
    },
});

const sectionIndicator = (key: PermissionSectionKey): boolean | 'indeterminate' => {
    const selected = form.config[key].length;

    if (selected === 0) return false;
    if (selected === ACTIONS.length) return true;

    return 'indeterminate';
};

const toggleSection = (key: PermissionSectionKey, value: boolean | 'indeterminate') => {
    form.config[key] = value === true ? [...ACTIONS] : [];
};

const save = (): void => {
    const url = props.project_role?.id ? route('project-role.update', props.project_role.id) : route('project-role.store');

    form.post(url, {
        preserveScroll: true,
    });
};

for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => {
            delete form.errors[key];
        },
        {
            debounce: 500,
            maxWait: 1000,
        },
    );
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :title="pageTitle">
        <form class="flex flex-col gap-6" @submit.prevent="save">
            <UCard title="Basic Information" description="Please fill the required fields." :ui="{ body: 'sm:py-0' }">
                <UFormField label="Name" name="name" required :error="form.errors.name" class="max-w-sm"
                    ><UInput v-model="form.name" placeholder="Enter project role name" class="w-full"
                /></UFormField>
            </UCard>

            <UCard title="Permissions" description="Define what actions this role can perform" :ui="{ body: 'sm:py-0' }">
                <div class="grid gap-4 md:grid-cols-3">
                    <UCard v-for="section in PERMISSION_SECTIONS" :key="section.key" :ui="{ body: 'flex flex-col gap-3 p-4' }">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold">{{ section.label }}</p>
                            <UCheckbox
                                :model-value="sectionIndicator(section.key)"
                                @update:model-value="(value) => toggleSection(section.key, value as boolean | 'indeterminate')"
                            />
                        </div>

                        <UFormField :name="`config.${section.key}`" :error="form.errors[`config.${section.key}`]">
                            <UCheckboxGroup v-model="form.config[section.key]" :items="actionItems" class="capitalize" />
                        </UFormField>
                    </UCard>
                </div>
            </UCard>

            <UCard title="Access Control" description="Restrict which data and fields this role can access" :ui="{ body: 'sm:py-0' }">
                <div class="flex flex-col gap-6">
                    <UFormField
                        label="Allowed Task Statuses"
                        name="config.allow_task_status"
                        description="Statuses this role is permitted to transition to (leave empty to allow all)"
                        :error="form.errors['config.allow_task_status']"
                        :ui="accessControlFieldUi"
                    >
                        <USelectMenu
                            v-model="form.config.allow_task_status"
                            :items="task_statuses"
                            label-key="name"
                            value-key="id"
                            multiple
                            placeholder="Select task statuses"
                            class="w-full"
                        >
                            <template #item-label="{ item }">
                                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                            </template>
                        </USelectMenu>
                    </UFormField>

                    <USeparator />

                    <UFormField
                        label="Allowed Update Task Fields"
                        name="config.allow_update_task_fields"
                        description="Task fields this role is allowed to modify (leave empty to allow all)"
                        :error="form.errors['config.allow_update_task_fields']"
                        :ui="accessControlFieldUi"
                    >
                        <UCheckboxGroup
                            v-model="form.config.allow_update_task_fields"
                            :items="TASK_FIELDS"
                            :ui="{ fieldset: 'grid grid-cols-2 gap-x-6 gap-y-3 md:grid-cols-4' }"
                        />
                    </UFormField>
                </div>
            </UCard>

            <div class="flex justify-end gap-3">
                <Link :href="route('project-role.index')">
                    <UButton label="Back" color="neutral" variant="outline" />
                </Link>
                <UButton label="Submit" type="submit" :loading="form.processing" :disabled="form.processing" />
            </div>
        </form>
    </AppLayout>
</template>
