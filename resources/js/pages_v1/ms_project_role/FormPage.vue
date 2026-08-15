<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { MsTaskStatus, ProjectRole } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed } from 'vue';

interface Props {
    project_role?: ProjectRole;
    task_statuses: MsTaskStatus[];
}

const props = defineProps<Props>();
const toast = useToast();

const isEdit = computed(() => !!props.project_role?.id);

type PermissionKey = 'task' | 'project_member' | 'sprint';

const PERMISSION_SECTIONS: { key: PermissionKey; label: string }[] = [
    { key: 'task', label: 'Task' },
    { key: 'project_member', label: 'Project Member' },
    { key: 'sprint', label: 'Sprint' },
];

const ACTIONS = ['create', 'update', 'delete'] as const;

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
] as const;

const form = useForm({
    _method: props.project_role?.id ? 'PUT' : 'POST',
    name: props.project_role?.name ?? '',
    config: {
        task: [...(props.project_role?.config?.task ?? [])] as string[],
        project_member: [...(props.project_role?.config?.project_member ?? [])] as string[],
        sprint: [...(props.project_role?.config?.sprint ?? [])] as string[],
        allow_task_status: [...(props.project_role?.config?.allow_task_status ?? [])] as string[],
        allow_update_task_fields: [...(props.project_role?.config?.allow_update_task_fields ?? [])] as string[],
    },
});

const save = () => {
    const url = isEdit.value ? route('project-role.update', props.project_role!.id) : route('project-role.store');

    form.post(url, {
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save project role', life: 3000 });
        },
    });
};

const goBack = () => {
    router.visit(route('project-role.index'));
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Project Role' : 'Create Project Role'" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <div class="flex items-center gap-3">
                <Button severity="secondary" text rounded @click="goBack" aria-label="Back">
                    <template #icon>
                        <Icon name="ArrowLeft" class="size-4" />
                    </template>
                </Button>
                <Heading
                    :title="isEdit ? 'Edit Project Role' : 'Create Project Role'"
                    :description="
                        isEdit ? 'Update project role permissions and configuration' : 'Define a new project role with permissions and access control'
                    "
                />
            </div>

            <form @submit.prevent="save" class="flex flex-col gap-4">
                <!-- Basic Info -->
                <Card>
                    <template #title>Basic Information</template>
                    <template #content>
                        <div class="flex max-w-sm flex-col gap-2">
                            <Label for="name"> Name <span class="text-red-500">*</span> </Label>
                            <InputText v-model="form.name" id="name" placeholder="Enter project role name" class="w-full" />
                            <InputError :message="form.errors.name" />
                        </div>
                    </template>
                </Card>

                <!-- Permissions -->
                <Card>
                    <template #title>Permissions</template>
                    <template #subtitle>Define what actions this role can perform</template>
                    <template #content>
                        <div class="py-4">
                            <div class="grid gap-4 md:grid-cols-3">
                                <div v-for="section in PERMISSION_SECTIONS" :key="section.key" class="flex flex-col gap-3 rounded-lg border p-4">
                                    <p class="font-semibold">{{ section.label }}</p>
                                    <div class="flex items-center gap-2">
                                        <Checkbox
                                            :model-value="form.config[section.key].length === ACTIONS.length"
                                            :indeterminate="
                                                form.config[section.key].length ? form.config[section.key].length != ACTIONS.length : false
                                            "
                                            binary
                                            @update:model-value="
                                                (value) => (value ? (form.config[section.key] = [...ACTIONS]) : (form.config[section.key] = []))
                                            "
                                        />
                                    </div>

                                    <div v-for="action in ACTIONS" :key="action" class="flex items-center gap-2">
                                        <Checkbox v-model="(form.config as any)[section.key]" :value="action" :inputId="`${section.key}_${action}`" />
                                        <label :for="`${section.key}_${action}`" class="cursor-pointer capitalize">
                                            {{ action }}
                                        </label>
                                    </div>

                                    <InputError :message="(form.errors as any)[`config.${section.key}`]" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Access Control -->
                <Card>
                    <template #title>Access Control</template>
                    <template #subtitle>Restrict which data and fields this role can access</template>
                    <template #content>
                        <div class="flex flex-col gap-6 py-4">
                            <!-- Allowed Task Statuses -->
                            <div class="grid grid-cols-3 gap-6">
                                <div>
                                    <Label class="text-base">Allowed Task Statuses</Label>
                                    <p class="text-sm text-muted-foreground">
                                        Statuses this role is permitted to transition to (leave empty to allow all)
                                    </p>
                                </div>

                                <div class="col-span-2">
                                    <MultiSelect
                                        v-model="form.config.allow_task_status"
                                        :options="task_statuses"
                                        option-label="name"
                                        option-value="id"
                                        placeholder="Select task statuses"
                                        class="w-full md:w-1/2"
                                        display="chip"
                                    >
                                        <template #option="{ option }">
                                            <Tag :value="option.name" :severity="option.severity" />
                                        </template>
                                        <template #chip="{ value }">
                                            <Tag
                                                :value="task_statuses.find((s) => s.id === value)?.name ?? value"
                                                :severity="task_statuses.find((s) => s.id === value)?.severity"
                                                class="mr-1"
                                            />
                                        </template>
                                    </MultiSelect>
                                    <InputError :message="(form.errors as any)['config.allow_task_status']" />
                                </div>
                            </div>

                            <Divider />

                            <!-- Allowed Update Task Fields -->
                            <div class="grid grid-cols-3 gap-6">
                                <div>
                                    <Label class="text-base">Allowed Update Task Fields</Label>
                                    <p class="text-sm text-muted-foreground">Task fields this role is allowed to modify (leave empty to allow all)</p>
                                </div>

                                <div class="col-span-2">
                                    <div class="grid grid-cols-2 gap-x-6 gap-y-3 md:grid-cols-4">
                                        <div v-for="field in TASK_FIELDS" :key="field.value" class="flex items-center gap-2">
                                            <Checkbox
                                                v-model="form.config.allow_update_task_fields"
                                                :value="field.value"
                                                :inputId="`field_${field.value}`"
                                            />
                                            <label :for="`field_${field.value}`" class="cursor-pointer">
                                                {{ field.label }}
                                            </label>
                                        </div>
                                    </div>
                                    <InputError :message="(form.errors as any)['config.allow_update_task_fields']" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" type="button" @click="goBack" />
                    <Button label="Save" type="submit" :loading="form.processing" :disabled="form.processing" />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
